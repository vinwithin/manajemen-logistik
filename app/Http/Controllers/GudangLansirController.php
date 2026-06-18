<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exports\RekapLansirGudangExport;
use App\Models\Cv;
use App\Models\GudangLansirHeader;
use App\Models\GudangLansirKendaraan;
use App\Models\GudangLansirPakan;
use App\Models\GudangLansirPenerima;
use App\Models\GudangLansirTim;
use App\Models\GudangMutasiStok;
use App\Models\GudangStok;
use App\Models\KodePakan;
use App\Models\Penerima;
use App\Models\PoPenerima;
use App\Models\PoPeriodeDokumen;
use App\Models\Supplier;
use App\Models\Tujuan;
use App\Services\Datatables\GudangLansirDatatableService;
use App\Services\GudangStokService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class GudangLansirController extends Controller
{
    protected $gudangLansirDatatableService;

    protected $gudangStokService;

    public function __construct(
        GudangLansirDatatableService $gudangLansirDatatableService,
        GudangStokService $gudangStokService
    ) {
        $this->gudangLansirDatatableService = $gudangLansirDatatableService;
        $this->gudangStokService = $gudangStokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->gudangLansirDatatableService->getData($request);
        }

        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->get();
        $kodePakans = KodePakan::orderBy('kode')->get();

        return view('pages.gudang.lansir.index', compact('gudangs', 'kodePakans'));
    }

    public function create(Request $request)
    {
        $gudangId = $request->gudang_id ?? session('active_gudang');
        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
        $kodePakans = KodePakan::orderBy('kode')->get();

        // Get stok tersedia per gudang (ambil semua, termasuk yang stok 0)
        $stokList = collect();
        if ($gudangId) {
            $stokList = GudangStok::where('tujuan_id', $gudangId)
                ->with('kodePakan')
                ->get();
        }

        $poPenerimaList = PoPenerima::with(['kendaraan.po', 'pakans.kodePakan', 'tujuan'])
            ->where('status', 'tiba')
            ->whereHas('tujuan', fn ($q) => $q->where('type', 'gudang'))
            ->when($gudangId, fn ($q) => $q->where('tujuan_id', $gudangId))
            ->whereDoesntHave('gudangLansirs') // Belum dilansir
            ->orderBy('tiba_at', 'desc')
            ->get();

        $penerimaList = Penerima::with('tujuan')
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'tujuan_id' => $p->tujuan_id,
                'tujuan_nama' => $p->tujuan?->nama ?? '',
                'ongkos_angkut' => (float) $p->ongkos_angkut,
                'ongkos_bongkar' => (float) $p->ongkos_bongkar,
            ]);

        $cvList = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();

        return view('pages.gudang.lansir.create', compact('gudangs', 'tujuans', 'kodePakans', 'gudangId', 'stokList', 'poPenerimaList', 'penerimaList', 'cvList'));
    }

    public function edit(string $id)
    {
        try {
            $header = GudangLansirHeader::with([
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tims',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
            $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
            $kodePakans = KodePakan::orderBy('kode')->get();

            $stokList = collect();
            $gudangId = $header->gudang_id;
            if ($gudangId) {
                $stokList = GudangStok::where('tujuan_id', $gudangId)
                    ->with('kodePakan')
                    ->get();
            }

            $poPenerimaList = PoPenerima::with(['kendaraan.po', 'pakans.kodePakan', 'tujuan'])
                ->where('status', 'tiba')
                ->whereHas('tujuan', fn ($q) => $q->where('type', 'gudang'))
                ->when($gudangId, fn ($q) => $q->where('tujuan_id', $gudangId))
                ->where(function ($q) use ($header) {
                    $q->whereDoesntHave('gudangLansirs')
                        ->orWhereHas('gudangLansirs', function ($q2) use ($header) {
                            $q2->whereHas('kendaraan', fn ($q3) => $q3->where('lansir_header_id', $header->id));
                        });
                })
                ->orderBy('tiba_at', 'desc')
                ->get();

            $penerimaList = Penerima::with('tujuan')
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'tujuan_id' => $p->tujuan_id,
                    'tujuan_nama' => $p->tujuan?->nama ?? '',
                    'ongkos_angkut' => (float) $p->ongkos_angkut,
                    'ongkos_bongkar' => (float) $p->ongkos_bongkar,
                ]);

            $cvList = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();

            return view('pages.gudang.lansir.sunting', compact('header', 'gudangs', 'tujuans', 'kodePakans', 'gudangId', 'stokList', 'poPenerimaList', 'penerimaList', 'cvList'));
        } catch (Exception $e) {
            return redirect()->route('gudang.lansir.index')->with('error', 'Data lansir tidak ditemukan: '.$e->getMessage());
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'gudang_id' => 'required|exists:tujuan,id',
            'cv_id' => 'nullable|exists:cv,id',
            'tanggal_lansir' => 'required|date',
            'catatan' => 'nullable|string',
            'kendaraans' => 'required|array|min:1',
            'kendaraans.*.no_polisi' => 'required|string|max:20',
            'kendaraans.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraans.*.penerimas' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.nama_penerima' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraans.*.penerimas.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraans.*.penerimas.*.pakans' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
        ]);

        try {
            $header = GudangLansirHeader::with([
                'kendaraans.penerimas.pakans',
            ])->findOrFail(decrypt($id));

            DB::transaction(function () use ($request, $header) {
                $gudangId = $header->gudang_id;

                // 1. Reverse semua stok yang keluar
                foreach ($header->kendaraans as $kendaraan) {
                    foreach ($kendaraan->penerimas as $penerima) {
                        foreach ($penerima->pakans as $pakan) {
                            // Kembalikan stok ke gudang
                            $stok = GudangStok::where('tujuan_id', $gudangId)
                                ->where('kode_pakan_id', $pakan->kode_pakan_id)
                                ->lockForUpdate()
                                ->first();

                            if ($stok) {
                                $stok->stok_kg += $pakan->jumlah_kg;
                                $stok->stok_karung += $pakan->jumlah_karung;
                                $stok->save();
                            } else {
                                GudangStok::create([
                                    'tujuan_id' => $gudangId,
                                    'kode_pakan_id' => $pakan->kode_pakan_id,
                                    'stok_kg' => $pakan->jumlah_kg,
                                    'stok_karung' => $pakan->jumlah_karung,
                                ]);
                            }

                            // Hapus mutasi yang lama
                            GudangMutasiStok::where('gudang_lansir_pakan_id', $pakan->id)->delete();
                        }
                    }
                }

                // 2. Hapus semua data lama (tims → pakans → penerimas → kendaraans)
                foreach ($header->kendaraans as $kendaraan) {
                    foreach ($kendaraan->penerimas as $penerima) {
                        $penerima->tims()->delete();
                        $penerima->pakans()->delete();
                    }
                    $kendaraan->penerimas()->delete();
                }
                $header->kendaraans()->delete();

                // 3. Update header
                $header->update([
                    'gudang_id' => $request->gudang_id,
                    'cv_id' => $request->cv_id ?? null,
                    'tanggal_lansir' => $request->tanggal_lansir,
                    'catatan' => $request->catatan ?? null,
                ]);

                // 4. Re-create semua data baru dengan logic yang sama seperti store
                $newGudangId = $request->gudang_id;

                foreach ($request->kendaraans ?? [] as $kendaraanData) {
                    if (empty(trim($kendaraanData['no_polisi'] ?? ''))) {
                        continue;
                    }

                    $kendaraan = GudangLansirKendaraan::create([
                        'lansir_header_id' => $header->id,
                        'no_polisi' => strtoupper(trim($kendaraanData['no_polisi'])),
                        'nama_sopir' => $kendaraanData['nama_sopir'] ?? null,
                        'created_by' => Auth::user()->id,
                    ]);

                    $totalKgKendaraan = 0;
                    $totalKarungKendaraan = 0;

                    foreach ($kendaraanData['penerimas'] ?? [] as $penerimaData) {
                        if (empty(trim($penerimaData['nama_penerima'] ?? ''))) {
                            continue;
                        }

                        $penerima = GudangLansirPenerima::create([
                            'kendaraan_id' => $kendaraan->id,
                            'nama_penerima' => $penerimaData['nama_penerima'],
                            'tujuan_id' => $penerimaData['tujuan_id'] ?? null,
                            'no_surat_jalan' => $penerimaData['no_surat_jalan'] ?? null,
                        ]);

                        foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                            if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                                continue;
                            }

                            $kodePakanId = $pakanData['kode_pakan_id'];
                            $jumlahKg = (float) $pakanData['jumlah_kg'];
                            $jumlahKarung = (int) ($pakanData['jumlah_karung'] ?? 0);

                            $stok = GudangStok::where('tujuan_id', $newGudangId)
                                ->where('kode_pakan_id', $kodePakanId)
                                ->lockForUpdate()
                                ->first();

                            $stokKgTersedia = $stok ? (float) $stok->stok_kg : 0.0;

                            if ($jumlahKg > $stokKgTersedia) {
                                throw new InsufficientStockException($stokKgTersedia);
                            }

                            $lansirPakan = GudangLansirPakan::create([
                                'penerima_id' => $penerima->id,
                                'kode_pakan_id' => $kodePakanId,
                                'jumlah_kg' => $jumlahKg,
                                'jumlah_karung' => $jumlahKarung,
                                'ongkos_oa' => $pakanData['ongkos_oa'] ?? 0,
                                'harga_pt_sum' => $pakanData['harga_pt_sum'] ?? 0,
                                'keterangan' => $pakanData['keterangan'] ?? null,
                            ]);

                            $stok->stok_kg = $stok->stok_kg - $jumlahKg;
                            $stok->stok_karung = max(0, $stok->stok_karung - $jumlahKarung);
                            $stok->save();

                            GudangMutasiStok::create([
                                'tujuan_id' => $newGudangId,
                                'kode_pakan_id' => $kodePakanId,
                                'tipe' => 'keluar',
                                'jumlah_kg' => $jumlahKg,
                                'jumlah_karung' => $jumlahKarung,
                                'referensi_tipe' => 'lansir_gudang_header',
                                'referensi_id' => $header->id,
                                'gudang_lansir_pakan_id' => $lansirPakan->id,
                                'saldo_kg_after' => $stok->stok_kg,
                                'saldo_karung_after' => $stok->stok_karung,
                            ]);

                            $totalKgKendaraan += $jumlahKg;
                            $totalKarungKendaraan += $jumlahKarung;
                        }

                        foreach ($penerimaData['tims'] ?? [] as $timData) {
                            if (empty(trim($timData['nama_tim'] ?? ''))) {
                                continue;
                            }
                            GudangLansirTim::create([
                                'penerima_id' => $penerima->id,
                                'nama_tim' => trim($timData['nama_tim']),
                                'jumlah_kg' => $timData['jumlah_kg'] ?? 0,
                                'upah_per_kg' => $timData['upah_per_kg'] ?? null,
                                'keterangan' => $timData['keterangan'] ?? null,
                            ]);
                        }
                    }

                    $kendaraan->update([
                        'total_kg' => $totalKgKendaraan,
                        'total_karung' => $totalKarungKendaraan,
                    ]);
                }
            });

            return redirect()->route('gudang.lansir.show', encrypt($header->id))
                ->with('success', 'Lansir gudang berhasil diperbarui!');
        } catch (InsufficientStockException $e) {
            return redirect()->back()->with('error', 'Stok tidak mencukupi: '.$e->getMessage())->withInput();
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage())->withInput();
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:tujuan,id',
            'cv_id' => 'required|exists:cv,id',
            'tanggal_lansir' => 'required|date',
            'catatan' => 'nullable|string',
            'kendaraans' => 'required|array|min:1',
            'kendaraans.*.no_polisi' => 'required|string|max:20',
            'kendaraans.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraans.*.penerimas' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.nama_penerima' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraans.*.penerimas.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraans.*.penerimas.*.pakans' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.pakans.*.keterangan' => 'nullable|string|max:255',
            'kendaraans.*.penerimas.*.tims' => 'nullable|array',
            'kendaraans.*.penerimas.*.tims.*.nama_tim' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.tims.*.jumlah_kg' => 'required|numeric|min:0.01',
            'kendaraans.*.penerimas.*.tims.*.upah_per_kg' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.tims.*.keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $header = $this->gudangStokService->prosesLansirGudangNested($request->all());

            return redirect()->route('gudang.lansir.show', encrypt($header->id))
                ->with('success', 'Lansir gudang berhasil disimpan. No Lansir: '.$header->no_lansir);
        } catch (InsufficientStockException $e) {
            return redirect()->back()
                ->with('error', 'Stok tidak mencukupi. Tersedia: '.$e->getMessage().' kg')
                ->withInput();
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(string $id)
    {
        try {
            $id = decrypt($id);

            $header = GudangLansirHeader::with([
                'gudang',
                'cv',
                'kendaraans.penerimas.tujuan',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tims',
                'kendaraans.penerimas.validator',
                'creator',
            ])->findOrFail($id);

            // Kode pakan unik untuk kolom pivot (dari semua kendaraan)
            $kodePakanList = $header->kendaraans
                ->flatMap(fn ($k) => $k->penerimas)
                ->flatMap(fn ($p) => $p->pakans)
                ->map(fn ($pk) => $pk->kodePakan)
                ->filter()
                ->unique('id')
                ->sortBy('kode')
                ->values();

            return view('pages.gudang.lansir.show', compact('header', 'kodePakanList'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Data lansir tidak ditemukan.');
        }
    }

    public function penerimaUpdateStatus(Request $request, string $id)
    {
        try {
            $id = decrypt($id);
            $penerima = GudangLansirPenerima::findOrFail($id);

            $request->validate([
                'status' => 'required|in:tiba,selesai',
                'bukti_tiba' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($request->status === 'tiba') {
                // Upload bukti tiba
                $buktiPath = null;
                if ($request->hasFile('bukti_tiba')) {
                    $file = $request->file('bukti_tiba');
                    $filename = 'bukti_tiba_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $buktiPath = $file->storeAs('bukti_tiba', $filename, 'public');
                }

                $penerima->update([
                    'status' => 'tiba',
                    'bukti_tiba' => $buktiPath,
                    'tiba_at' => now(),
                    'validasi_oleh' => auth()->id(),
                ]);

                return redirect()->back()->with('success', 'Status penerima berhasil diubah menjadi Tiba.');
            }

            if ($request->status === 'selesai') {
                if ($penerima->status !== 'tiba') {
                    return redirect()->back()->with('error', 'Penerima harus berstatus Tiba terlebih dahulu.');
                }

                $penerima->update(['status' => 'selesai']);

                return redirect()->back()->with('success', 'Status penerima berhasil diubah menjadi Selesai.');
            }

            return redirect()->back()->with('error', 'Status tidak valid.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal update status: '.$e->getMessage());
        }
    }

    public function getPoPenerimaData(string $id)
    {
        try {
            $poPenerima = PoPenerima::with(['pakans.kodePakan', 'tujuan', 'kendaraan.po'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'po_no' => $poPenerima->kendaraan->po->no_po,
                    'nama_penerima' => $poPenerima->nama_penerima,
                    'tujuan_id' => $poPenerima->tujuan_id,
                    'tujuan_nama' => $poPenerima->tujuan->nama,
                    'tiba_at' => $poPenerima->tiba_at?->format('d/m/Y H:i'),
                    'pakans' => $poPenerima->pakans->map(fn ($p) => [
                        'kode_pakan_id' => $p->kode_pakan_id,
                        'kode' => $p->kodePakan->kode,
                        'nama' => $p->kodePakan->nama,
                        'jumlah_kg' => $p->jumlah_kg,
                        'jumlah_karung' => $p->jumlah_karung,
                        'ongkos_oa' => $p->ongkos_oa,
                        'harga_pt_sum' => $p->harga_pt_sum ?? 0,
                    ]),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan: '.$e->getMessage(),
            ], 404);
        }
    }

    public function timDestroy(string $id)
    {
        try {
            $id = decrypt($id);
            $tim = GudangLansirTim::with('penerima')->findOrFail($id);

            $tim->delete();

            return redirect()->back()->with('success', 'Tim bongkar berhasil dihapus!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus tim bongkar: '.$e->getMessage());
        }
    }

    public function exportRekap(Request $request)
    {
        $from = $request->dari_tanggal ?: null;
        $to = $request->sampai_tanggal ?: null;
        $gudangId = $request->gudang_id ? (int) $request->gudang_id : null;

        $filename = 'rekap-lansir-gudang';
        if ($from) {
            $filename .= '-'.$from;
        }
        if ($to) {
            $filename .= '-sd-'.$to;
        }
        $filename .= '.xlsx';

        return Excel::download(
            new RekapLansirGudangExport($from, $to, $gudangId),
            $filename
        );
    }

    public function exportPdfPtSumConfirm(Request $request)
    {
        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
        $userCvs = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $suppliers = Supplier::orderBy('nama')->get();
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $gudangId = $request->gudang_id;
        $supplierId = $request->supplier_id;
        $tujuanIds = $request->tujuan_ids
            ? array_filter(array_map('intval', explode(',', $request->tujuan_ids)))
            : [];
        $selectedKendaraanIds = $request->kendaraan_ids ? array_filter(explode(',', $request->kendaraan_ids)) : [];
        $lansirCount = null;
        $cvNama = null;
        $dokumen = null;
        $noSuratSuggest = null;
        $kendaraanList = collect();

        if ($cvId && $from && $to) {
            $query = GudangLansirHeader::where('cv_id', $cvId)
                ->whereDate('tanggal_lansir', '>=', $from)
                ->whereDate('tanggal_lansir', '<=', $to)
                ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId));

            // Filter tujuan: mendukung multiple ID
            if (!empty($tujuanIds)) {
                $query->whereHas(
                    'kendaraans.penerimas',
                    fn ($q) => $q->whereIn('tujuan_id', $tujuanIds)
                );
            }

            $lansirCount = $query->count();

            // Ambil daftar kendaraan untuk filter plat mobil
            $kendaraanList = GudangLansirKendaraan::whereHas('lansirHeader', function ($q) use ($cvId, $from, $to, $gudangId, $tujuanIds) {
                $q->where('cv_id', $cvId)
                    ->whereDate('tanggal_lansir', '>=', $from)
                    ->whereDate('tanggal_lansir', '<=', $to)
                    ->when($gudangId, fn ($q2) => $q2->where('gudang_id', $gudangId))
                    ->when(!empty($tujuanIds), fn ($q2) => $q2->whereHas('kendaraans.penerimas', fn ($q3) => $q3->whereIn('tujuan_id', $tujuanIds)));
            })
                ->with('lansirHeader')
                ->orderBy('no_polisi')
                ->get(['id', 'no_polisi', 'lansir_header_id']);

            $cv = Cv::find($cvId);
            $cvNama = $cv?->nama_cv;

            $dokumen = PoPeriodeDokumen::where('cv_id', $cvId)
                ->where('dari', $from)
                ->where('sampai', $to)
                ->where('tipe', 'gudang_ptsum')
                ->first();

            if (! $dokumen && $cv) {
                $generated = PoPeriodeDokumen::generateNoSurat($cv, 'gudang_ptsum', $from);
                $noSuratSuggest = $generated['no_surat'];
            }
        }

        return view('pages.gudang.lansir.export-ptsum-confirm', compact(
            'gudangs',
            'userCvs',
            'suppliers',
            'tujuans',
            'cvId',
            'from',
            'to',
            'gudangId',
            'supplierId',
            'tujuanIds',
            'lansirCount',
            'cvNama',
            'dokumen',
            'noSuratSuggest',
            'kendaraanList',
            'selectedKendaraanIds'
        ));
    }

    public function exportPdfPtSum(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'tujuan_ids' => 'required|string',
        ], [
            'from.required' => 'Tanggal awal periode wajib diisi.',
            'to.required' => 'Tanggal akhir periode wajib diisi.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            'tujuan_ids.required' => 'Pilih tujuan terlebih dahulu.',
        ]);

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $gudangId = $request->gudang_id;
        $supplierId = $request->supplier_id;
        $tujuanIds = array_filter(array_map('intval', explode(',', $request->tujuan_ids)));
        $noSuratInput = $request->no_surat;
        $tanggalSurat = $request->tanggal_surat;
        $cpi = $request->cpi;
        $kendaraanIds = $request->kendaraan_ids
            ? array_filter(array_map('intval', explode(',', $request->kendaraan_ids)))
            : [];

        if (! $cvId) {
            return redirect()->route('gudang.lansir.export-ptsum-confirm')
                ->with('error', 'Pilih CV terlebih dahulu.');
        }

        $cv = Cv::find($cvId);
        $noSurat = null;

        // Ambil tujuan pertama untuk tipe dokumen
        $tujuanPrimary = Tujuan::find($tujuanIds[0] ?? null);
        $tujuanTypeMap = [
            'co_farm' => 'CFJ',
            'rent_farm' => 'RFJ',
            'gudang' => 'GJ',
            'direct' => 'DRC',
        ];
        $tujuanType = $tujuanTypeMap[$tujuanPrimary?->type] ?? 'DRC';

        if ($noSuratInput && $from && $to && $cv) {
            $dokumen = \DB::transaction(function () use ($cvId, $from, $to, $cv, $request, $noSuratInput, $cpi) {
                $existing = PoPeriodeDokumen::where('cv_id', $cvId)
                    ->where('dari', $from)
                    ->where('sampai', $to)
                    ->where('tipe', 'gudang_ptsum')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'no_surat' => $noSuratInput,
                        'cpi' => $cpi,
                        'catatan' => $request->catatan,
                    ]);
                    return $existing;
                }

                $generated = PoPeriodeDokumen::generateNoSurat($cv, 'gudang_ptsum', $from);

                return PoPeriodeDokumen::create([
                    'cv_id' => $cvId,
                    'dari' => $from,
                    'sampai' => $to,
                    'tipe' => 'gudang_ptsum',
                    'urutan' => $generated['urutan'],
                    'cpi' => $cpi,
                    'no_surat' => $noSuratInput,
                    'catatan' => $request->catatan,
                    'created_by' => auth()->id(),
                ]);
            });

            $noSurat = $dokumen->no_surat;
        }

        $query = GudangLansirHeader::with([
            'gudang',
            'cv',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.poPenerima.kendaraan.po.cv',
        ])->whereDate('tanggal_lansir', '>=', $from)
            ->whereDate('tanggal_lansir', '<=', $to)
            ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
            ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

        if ($supplierId) {
            $query->whereHas(
                'kendaraans.penerimas.poPenerima.kendaraan',
                fn ($q) => $q->where('supplier_id', $supplierId)
            );
        }

        // Filter tujuan: mendukung multiple ID
        if (!empty($tujuanIds)) {
            $query->whereHas(
                'kendaraans.penerimas',
                fn ($q) => $q->whereIn('tujuan_id', $tujuanIds)
            );
        }

        // Filter kendaraan spesifik (plat mobil)
        if (! empty($kendaraanIds)) {
            $query->whereHas('kendaraans', fn ($q) => $q->whereIn('id', $kendaraanIds));
        }

        $headers = $query->orderBy('tanggal_lansir')->get();

        // Filter penerima berdasarkan tujuan jika ada filter
        if (!empty($tujuanIds)) {
            foreach ($headers as $header) {
                foreach ($header->kendaraans as $kendaraan) {
                    $kendaraan->setRelation('penerimas', $kendaraan->penerimas->filter(
                        fn ($p) => in_array($p->tujuan_id, $tujuanIds)
                    )->values());
                }
                $header->setRelation('kendaraans', $header->kendaraans->filter(
                    fn ($k) => $k->penerimas->isNotEmpty()
                )->values());
            }
            $headers = $headers->filter(fn ($h) => $h->kendaraans->isNotEmpty())->values();
        }

        // Jika ada filter kendaraan, filter kendaraan yang ditampilkan
        if (! empty($kendaraanIds)) {
            foreach ($headers as $header) {
                $header->setRelation('kendaraans', $header->kendaraans->filter(
                    fn ($k) => in_array($k->id, $kendaraanIds)
                )->values());
            }
        }

        // Nama tujuan untuk dokumen — gabungkan jika multiple
        $tujuanNamaList = Tujuan::whereIn('id', $tujuanIds)->pluck('nama')->join(' & ');
        $tujuanNama = $cpi ?? $tujuanNamaList;       $pdf = Pdf::loadView(
            'pdf.gudang-lansir-ptsum',
            compact('headers', 'from', 'to', 'noSurat', 'tujuanNama', 'tanggalSurat')
        )
            ->setPaper('legal', 'landscape')
            ->setOption('margin-top', 10)->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)->setOption('margin-right', 10);

        $cvNama = $headers->first()?->cv?->nama_cv ?? 'CV';
        $filename = 'Lansir-Gudang-PTSum-'.str_replace(' ', '-', $cvNama).'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    public function exportPdfSupplierConfirm(Request $request)
    {
        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
        $cvList = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $suppliers = Supplier::orderBy('nama')->get();
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();

        $from = $request->from;
        $to = $request->to;
        $gudangId = $request->gudang_id;
        $cvId = $request->cv_id;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;
        $lansirCount = null;

        if ($from && $to) {
            $query = GudangLansirHeader::whereDate('tanggal_lansir', '>=', $from)
                ->whereDate('tanggal_lansir', '<=', $to)
                ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

            // Filter supplier
            if ($supplierId) {
                $query->whereHas(
                    'kendaraans.penerimas.poPenerima.kendaraan',
                    fn ($q) => $q->where('supplier_id', $supplierId)
                );
            }

            // Filter tujuan
            if ($tujuanId) {
                $query->whereHas(
                    'kendaraans.penerimas',
                    fn ($q) => $q->where('tujuan_id', $tujuanId)
                );
            }

            $lansirCount = $query->count();
        }

        return view('pages.gudang.lansir.export-supplier-confirm', compact(
            'gudangs',
            'cvList',
            'suppliers',
            'tujuans',
            'from',
            'to',
            'gudangId',
            'cvId',
            'supplierId',
            'tujuanId',
            'lansirCount'
        ));
    }

    public function exportPdfSupplier(Request $request)
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $from = $request->from;
        $to = $request->to;
        $gudangId = $request->gudang_id;
        $cvId = $request->cv_id;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;

        $query = GudangLansirHeader::with([
            'gudang',
            'cv',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.poPenerima.kendaraan.po.cv',
        ])->whereDate('tanggal_lansir', '>=', $from)
            ->whereDate('tanggal_lansir', '<=', $to)
            ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
            ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

        // Filter supplier
        if ($supplierId) {
            $query->whereHas(
                'kendaraans.penerimas.poPenerima.kendaraan',
                fn ($q) => $q->where('supplier_id', $supplierId)
            );
        }

        // Filter tujuan
        if ($tujuanId) {
            $query->whereHas(
                'kendaraans.penerimas',
                fn ($q) => $q->where('tujuan_id', $tujuanId)
            );
        }

        $headers = $query->orderBy('tanggal_lansir')->get();

        $pdf = Pdf::loadView(
            'pdf.gudang-lansir-supplier',
            compact('headers', 'from', 'to')
        )
            ->setPaper('legal', 'landscape')
            ->setOption('margin-top', 10)->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)->setOption('margin-right', 10);

        return $pdf->stream('lansir-gudang-supplier-'.now()->format('Ymd').'.pdf');
    }
}
