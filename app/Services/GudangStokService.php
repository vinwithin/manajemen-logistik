<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\GudangLansir;
use App\Models\GudangLansirKendaraan;
use App\Models\GudangLansirMobil;
use App\Models\GudangLansirPakan;
use App\Models\GudangLansirPenerima;
use App\Models\GudangLansirTim;
use App\Models\GudangMutasiStok;
use App\Models\GudangStok;
use App\Models\PoPenerima;
use App\Models\PoPenerimaPakan;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GudangStokService
{
    /**
     * Proses stok masuk dari PO item yang selesai.
     * Harus dipanggil di dalam DB::transaction() yang sudah ada.
     *
     * @param  int  $kodePakanId  ID kode pakan yang dikirim (wajib disediakan oleh caller)
     *
     * @throws \RuntimeException
     */
    public function prosesStokMasuk(PurchaseOrderItem $item, int $kodePakanId): void
    {
        $tujuanId = $item->tujuan_id;

        if (! $tujuanId || ! $kodePakanId) {
            throw new \RuntimeException(
                'tujuan_id atau kode_pakan_id tidak ditemukan pada item PO id='.$item->id
            );
        }

        $jumlahKg = (float) ($item->berat ?? 0);
        $jumlahKarung = (int) ($item->jumlah_karung ?? 0);

        // Lock baris gudang_stok agar tidak ada race condition
        $stok = GudangStok::where('tujuan_id', $tujuanId)
            ->where('kode_pakan_id', $kodePakanId)
            ->lockForUpdate()
            ->first();

        if ($stok) {
            $stok->stok_kg = $stok->stok_kg + $jumlahKg;
            $stok->stok_karung = $stok->stok_karung + $jumlahKarung;
            $stok->save();
        } else {
            $stok = GudangStok::create([
                'tujuan_id' => $tujuanId,
                'kode_pakan_id' => $kodePakanId,
                'stok_kg' => $jumlahKg,
                'stok_karung' => $jumlahKarung,
            ]);
        }

        GudangMutasiStok::create([
            'tujuan_id' => $tujuanId,
            'kode_pakan_id' => $kodePakanId,
            'tipe' => 'masuk',
            'jumlah_kg' => $jumlahKg,
            'jumlah_karung' => $jumlahKarung,
            'referensi_tipe' => 'po_item',
            'referensi_id' => $item->id,
            'saldo_kg_after' => $stok->stok_kg,
            'saldo_karung_after' => $stok->stok_karung,
        ]);
    }

    /**
     * Proses stok masuk dari PO penerima pakan yang tiba.
     * Harus dipanggil di dalam DB::transaction() yang sudah ada.
     *
     * @throws \RuntimeException
     */
    public function prosesStokMasukPoPenerima(PoPenerima $penerima, PoPenerimaPakan $pakan): void
    {
        $tujuanId = $penerima->tujuan_id;
        $kodePakanId = $pakan->kode_pakan_id;

        if (! $tujuanId || ! $kodePakanId) {
            throw new \RuntimeException(
                'tujuan_id atau kode_pakan_id tidak ditemukan pada penerima id='.$penerima->id
            );
        }

        $jumlahKg = (float) ($pakan->jumlah_kg ?? 0);
        $jumlahKarung = (int) ($pakan->jumlah_karung ?? 0);

        // Lock baris gudang_stok agar tidak ada race condition
        $stok = GudangStok::where('tujuan_id', $tujuanId)
            ->where('kode_pakan_id', $kodePakanId)
            ->lockForUpdate()
            ->first();

        if ($stok) {
            $stok->stok_kg = $stok->stok_kg + $jumlahKg;
            $stok->stok_karung = $stok->stok_karung + $jumlahKarung;
            $stok->save();
        } else {
            $stok = GudangStok::create([
                'tujuan_id' => $tujuanId,
                'kode_pakan_id' => $kodePakanId,
                'stok_kg' => $jumlahKg,
                'stok_karung' => $jumlahKarung,
            ]);
        }

        GudangMutasiStok::create([
            'tujuan_id' => $tujuanId,
            'kode_pakan_id' => $kodePakanId,
            'tipe' => 'masuk',
            'jumlah_kg' => $jumlahKg,
            'jumlah_karung' => $jumlahKarung,
            'referensi_tipe' => 'po_penerima_pakan',
            'referensi_id' => $pakan->id,
            'po_penerima_id' => $penerima->id,
            'saldo_kg_after' => $stok->stok_kg,
            'saldo_karung_after' => $stok->stok_karung,
        ]);
    }

    /**
     * Proses lansir (pengeluaran) stok dari gudang.
     * Method ini membuat transaksi sendiri.
     *
     * @param  array{
     *   tujuan_id: int,
     *   kode_pakan_id: int,
     *   jumlah_kg: float,
     *   jumlah_karung?: int,
     *   ongkos_per_kg?: float|null,
     *   upah_per_kg?: float|null,
     *   catatan?: string|null,
     *   mobils?: array<array{no_polisi: string, berat?: float|null, ongkos?: float|null}>,
     *   tims?: array<array{nama_tim: string, upah?: float|null}>,
     * } $data
     *
     * @throws InsufficientStockException
     * @throws \Exception
     */
    public function prosesLansirGudang(array $data): GudangLansir
    {
        $jumlahKg = (float) $data['jumlah_kg'];
        $jumlahKarung = (int) ($data['jumlah_karung'] ?? 0);

        if ($jumlahKg <= 0) {
            throw new \InvalidArgumentException('Jumlah kg harus lebih dari 0.');
        }

        return DB::transaction(function () use ($data, $jumlahKg, $jumlahKarung) {
            $tujuanId = $data['tujuan_id'];
            $kodePakanId = $data['kode_pakan_id'];

            // Lock baris stok untuk mencegah race condition
            $stok = GudangStok::where('tujuan_id', $tujuanId)
                ->where('kode_pakan_id', $kodePakanId)
                ->lockForUpdate()
                ->first();

            $stokKgTersedia = $stok ? (float) $stok->stok_kg : 0.0;

            if ($jumlahKg > $stokKgTersedia) {
                throw new InsufficientStockException($stokKgTersedia);
            }

            // Buat record lansir
            $lansir = GudangLansir::create([
                'tujuan_id' => $tujuanId,
                'kode_pakan_id' => $kodePakanId,
                'jumlah_kg' => $jumlahKg,
                'jumlah_karung' => $jumlahKarung,
                'ongkos_per_kg' => $data['ongkos_per_kg'] ?? null,
                'upah_per_kg' => $data['upah_per_kg'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Simpan detail mobil
            foreach ($data['mobils'] ?? [] as $mobil) {
                if (empty(trim($mobil['no_polisi'] ?? ''))) {
                    continue;
                }
                GudangLansirMobil::create([
                    'lansir_id' => $lansir->id,
                    'no_polisi' => strtoupper(trim($mobil['no_polisi'])),
                    'nama_sopir' => $mobil['nama_sopir'] ?? null,
                    'berat' => $mobil['berat'] ?? null,
                    'jumlah_karung' => (int) ($mobil['jumlah_karung'] ?? 0),
                    'ongkos' => $mobil['ongkos'] ?? null,
                ]);
            }

            // Simpan detail tim bongkar
            foreach ($data['tims'] ?? [] as $tim) {
                if (empty(trim($tim['nama_tim'] ?? ''))) {
                    continue;
                }
                GudangLansirTim::create([
                    'lansir_id' => $lansir->id,
                    'nama_tim' => trim($tim['nama_tim']),
                    'berat' => $tim['berat'] ?? null,
                    'jumlah_karung' => (int) ($tim['jumlah_karung'] ?? 0),
                    'upah' => $tim['upah'] ?? null,
                ]);
            }

            // Kurangi stok
            $stok->stok_kg = $stok->stok_kg - $jumlahKg;
            $stok->stok_karung = max(0, $stok->stok_karung - $jumlahKarung);
            $stok->save();

            // Catat mutasi keluar
            GudangMutasiStok::create([
                'tujuan_id' => $tujuanId,
                'kode_pakan_id' => $kodePakanId,
                'tipe' => 'keluar',
                'jumlah_kg' => $jumlahKg,
                'jumlah_karung' => $jumlahKarung,
                'referensi_tipe' => 'lansir_gudang',
                'referensi_id' => $lansir->id,
                'saldo_kg_after' => $stok->stok_kg,
                'saldo_karung_after' => $stok->stok_karung,
            ]);

            return $lansir;
        });
    }

    /**
     * Proses lansir gudang dengan struktur nested (kendaraan > penerima > pakan).
     * Mengurangi stok untuk setiap pakan yang dilansir.
     *
     * @throws InsufficientStockException
     * @throws \Exception
     */
    public function prosesLansirGudangNested(array $data): GudangLansirKendaraan
    {
        return DB::transaction(function () use ($data) {
            $gudangId = $data['gudang_id'];

            // Buat kendaraan
            $kendaraan = GudangLansirKendaraan::create([
                'gudang_id' => $gudangId,
                'no_polisi' => strtoupper(trim($data['no_polisi'])),
                'nama_sopir' => $data['nama_sopir'] ?? null,
                'no_surat_jalan' => $data['no_surat_jalan'] ?? null,
                'tanggal_lansir' => $data['tanggal_lansir'],
                'catatan' => $data['catatan'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $totalKg = 0;
            $totalKarung = 0;

            // Loop penerima
            foreach ($data['penerimas'] ?? [] as $penerimaData) {
                if (empty(trim($penerimaData['nama_penerima'] ?? ''))) {
                    continue;
                }

                $penerima = GudangLansirPenerima::create([
                    'kendaraan_id' => $kendaraan->id,
                    'nama_penerima' => $penerimaData['nama_penerima'],
                    'tujuan_id' => $penerimaData['tujuan_id'] ?? null,
                ]);

                // Loop pakan per penerima
                foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                    if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                        continue;
                    }

                    $kodePakanId = $pakanData['kode_pakan_id'];
                    $jumlahKg = (float) $pakanData['jumlah_kg'];
                    $jumlahKarung = (int) ($pakanData['jumlah_karung'] ?? 0);

                    // Cek dan kurangi stok
                    $stok = GudangStok::where('tujuan_id', $gudangId)
                        ->where('kode_pakan_id', $kodePakanId)
                        ->lockForUpdate()
                        ->first();

                    $stokKgTersedia = $stok ? (float) $stok->stok_kg : 0.0;

                    if ($jumlahKg > $stokKgTersedia) {
                        throw new InsufficientStockException($stokKgTersedia);
                    }

                    // Simpan pakan
                    GudangLansirPakan::create([
                        'penerima_id' => $penerima->id,
                        'kode_pakan_id' => $kodePakanId,
                        'jumlah_kg' => $jumlahKg,
                        'jumlah_karung' => $jumlahKarung,
                        'ongkos_oa' => $pakanData['ongkos_oa'] ?? 0,
                    ]);

                    // Kurangi stok
                    $stok->stok_kg = $stok->stok_kg - $jumlahKg;
                    $stok->stok_karung = max(0, $stok->stok_karung - $jumlahKarung);
                    $stok->save();

                    // Catat mutasi keluar
                    GudangMutasiStok::create([
                        'tujuan_id' => $gudangId,
                        'kode_pakan_id' => $kodePakanId,
                        'tipe' => 'keluar',
                        'jumlah_kg' => $jumlahKg,
                        'jumlah_karung' => $jumlahKarung,
                        'referensi_tipe' => 'lansir_gudang',
                        'referensi_id' => $kendaraan->id,
                        'saldo_kg_after' => $stok->stok_kg,
                        'saldo_karung_after' => $stok->stok_karung,
                    ]);

                    $totalKg += $jumlahKg;
                    $totalKarung += $jumlahKarung;
                }

                // Loop tim bongkar per penerima
                foreach ($penerimaData['tims'] ?? [] as $timData) {
                    if (empty(trim($timData['nama_tim'] ?? ''))) {
                        continue;
                    }
                    GudangLansirTim::create([
                        'penerima_id' => $penerima->id,
                        'nama_tim' => trim($timData['nama_tim']),
                        'jumlah_kg' => $timData['jumlah_kg'] ?? 0,
                        'upah_per_kg' => $timData['upah_per_kg'] ?? null,
                    ]);
                }
            }

            // Update total kendaraan
            $kendaraan->update([
                'total_kg' => $totalKg,
                'total_karung' => $totalKarung,
            ]);

            return $kendaraan;
        });
    }
}
