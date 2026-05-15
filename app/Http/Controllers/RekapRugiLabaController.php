<?php

namespace App\Http\Controllers;

use App\Exports\RugiLabaExport;
use App\Models\Cv;
use App\Models\PoPenerimaPakan;
use App\Models\RugiLaba;
use App\Models\RugiLabaHarian;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RekapRugiLabaController extends Controller
{
    // ── Daftar per CV ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $cvList  = Cv::orderBy('nama_cv')->get();
        $cvId    = $request->cv_id ?? session('active_cv');
        $tahun   = (int) ($request->tahun ?? now()->year);

        $records = collect();
        $cv      = null;
        $summary = []; // ringkasan per bulan untuk tabel

        if ($cvId) {
            $cv      = Cv::find($cvId);
            $records = RugiLaba::where('cv_id', $cvId)
                ->where('tahun', $tahun)
                ->orderBy('bulan')
                ->get();

            // Hitung ringkasan per bulan (pembelian, penjualan, laba)
            foreach ($records as $rl) {
                $auto = $this->hitungOtomatis($cvId, $rl->bulan, $tahun);

                $totalBiaya = $rl->total_biaya_operasional
                    + $auto['upahBongkarOtomatis']
                    + $auto['mobilLokalOtomatis'];

                $labaKotor  = $auto['totalPenjualan'] - $auto['totalPembelian'];
                $pph21      = $labaKotor > 0 ? $labaKotor * 0.005 : 0;
                $labaBersih = $labaKotor - $totalBiaya - $pph21 - (float) $rl->potongan_voucher;

                $summary[$rl->id] = [
                    'totalPembelian'      => $auto['totalPembelian'],
                    'totalPenjualan'      => $auto['totalPenjualan'],
                    'totalBiaya'          => $totalBiaya,
                    'labaKotor'           => $labaKotor,
                    'labaBersih'          => $labaBersih,
                ];
            }
        }

        return view('pages.keuangan.rugi-laba.index', compact(
            'cvList', 'cvId', 'tahun', 'cv', 'records', 'summary'
        ));
    }

    // ── Form Create ───────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $cvList = Cv::orderBy('nama_cv')->get();
        $cvId   = $request->cv_id ?? session('active_cv');
        $bulan  = (int) ($request->bulan ?? now()->month);
        $tahun  = (int) ($request->tahun ?? now()->year);

        // Cek apakah sudah ada
        $existing = RugiLaba::where('cv_id', $cvId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($existing) {
            return redirect()->route('keuangan.rugi-laba.show', $existing->id);
        }

        $cv = $cvId ? Cv::find($cvId) : null;

        // Hitung data otomatis untuk preview
        $autoData = $cvId ? $this->hitungOtomatis($cvId, $bulan, $tahun) : null;

        return view('pages.keuangan.rugi-laba.create', compact(
            'cvList', 'cvId', 'bulan', 'tahun', 'cv', 'autoData'
        ));
    }

    // ── Simpan ────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'cv_id' => 'required|exists:cv,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2099',
        ]);

        $fields = [
            'gaji', 'atk', 'pembayaran_supplier_lintas', 'pembayaran_mobil_lokal',
            'sharing_fee', 'sharing_profit', 'perjalanan_dinas', 'entertain',
            'adm_bank', 'upah_bongkar', 'upah_muat', 'upah_bongkar_muat',
            'biaya_lain_lain', 'bbm', 'listrik', 'pdam', 'potongan_voucher',
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = (float) ($request->input($f, 0));
        }
        $data['catatan']       = $request->catatan;
        $data['periode_label'] = RugiLaba::namaBulan((int) $request->bulan) . ' ' . $request->tahun;
        $data['created_by']    = Auth::id();

        $rl = RugiLaba::updateOrCreate(
            ['cv_id' => $request->cv_id, 'bulan' => $request->bulan, 'tahun' => $request->tahun],
            $data
        );

        return redirect()->route('keuangan.rugi-laba.show', $rl->id)
            ->with('success', 'Data Rugi Laba berhasil disimpan.');
    }

    // ── Detail / Laporan ──────────────────────────────────────────────────────
    public function show(string $id)
    {
        $rl   = RugiLaba::with('cv')->findOrFail($id);
        $data = $this->hitungOtomatis($rl->cv_id, $rl->bulan, $rl->tahun);
        $data['rl'] = $rl;

        // Kalkulasi final
        $data['totalBiayaOperasional'] = $rl->total_biaya_operasional
            + $data['upahBongkarOtomatis']
            + $data['mobilLokalOtomatis'];

        $data['labaKotor']  = $data['totalPenjualan'] - $data['totalPembelian'];
        $data['pph21']      = $data['labaKotor'] > 0 ? $data['labaKotor'] * 0.005 : 0;
        $data['voucher']      = $data['pph21'];
        $data['labaBersih'] = $data['labaKotor'] - $data['totalBiayaOperasional'] - $data['pph21'] - $data['voucher'];

        return view('pages.keuangan.rugi-laba.show', compact('rl', 'data'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(string $id)
    {
        $rl     = RugiLaba::with('cv')->findOrFail($id);
        $cvList = Cv::orderBy('nama_cv')->get();
        $autoData = $this->hitungOtomatis($rl->cv_id, $rl->bulan, $rl->tahun);

        return view('pages.keuangan.rugi-laba.create', [
            'cvList'   => $cvList,
            'cvId'     => $rl->cv_id,
            'bulan'    => $rl->bulan,
            'tahun'    => $rl->tahun,
            'cv'       => $rl->cv,
            'autoData' => $autoData,
            'rl'       => $rl,
        ]);
    }

    // ── Input Harian ──────────────────────────────────────────────────────────
    public function harian(string $id)
    {
        $rl     = RugiLaba::with('cv')->findOrFail($id);
        $labels = RugiLabaHarian::labelBiaya();

        // Entri harian dikelompokkan per tanggal
        $entries = RugiLabaHarian::where('rugi_laba_id', $rl->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn($e) => $e->tanggal->format('Y-m-d'));

        // Total per kode biaya
        $totals = $rl->totalHarian();

        return view('pages.keuangan.rugi-laba.harian', compact('rl', 'labels', 'entries', 'totals'));
    }

    public function storeHarian(Request $request, string $id)
    {
        $request->validate([
            'tanggal'    => 'required|date',
            'kode_biaya' => 'required|string',
            'nominal'    => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $rl = RugiLaba::findOrFail($id);

        RugiLabaHarian::create([
            'rugi_laba_id' => $rl->id,
            'tanggal'      => $request->tanggal,
            'kode_biaya'   => $request->kode_biaya,
            'keterangan'   => $request->keterangan,
            'nominal'      => $request->nominal,
            'created_by'   => Auth::id(),
        ]);

        // Sync total ke kolom rugi_laba (agar laporan tetap konsisten)
        $this->syncTotalHarianKeRugiLaba($rl);

        return redirect()->back()->with('success', 'Entri harian berhasil disimpan.');
    }

    public function destroyHarian(string $harianId)
    {
        $harian = RugiLabaHarian::findOrFail($harianId);
        $rl     = $harian->rugLaba;
        $harian->delete();

        $this->syncTotalHarianKeRugiLaba($rl);

        return response()->json(['success' => true, 'message' => 'Entri berhasil dihapus.']);
    }

    /** Sync total entri harian ke kolom di tabel rugi_laba */
    private function syncTotalHarianKeRugiLaba(RugiLaba $rl): void
    {
        $totals = $rl->totalHarian();
        $fields = array_keys(RugiLabaHarian::labelBiaya());

        $update = [];
        foreach ($fields as $field) {
            $update[$field] = $totals[$field] ?? 0;
        }

        $rl->update($update);
    }

    // ── Export Excel ──────────────────────────────────────────────────────────
    public function export(string $id)    {
        $rl   = RugiLaba::with('cv')->findOrFail($id);
        $data = $this->hitungOtomatis($rl->cv_id, $rl->bulan, $rl->tahun);
        $data['rl'] = $rl;

        $data['totalBiayaOperasional'] = $rl->total_biaya_operasional
            + $data['upahBongkarOtomatis']
            + $data['mobilLokalOtomatis'];

        $data['labaKotor']  = $data['totalPenjualan'] - $data['totalPembelian'];
        $data['pph21']      = $data['labaKotor'] > 0 ? $data['labaKotor'] * 0.005 : 0;
        $data['labaBersih'] = $data['labaKotor'] - $data['totalBiayaOperasional'] - $data['pph21'] - (float) $rl->potongan_voucher;

        $periode  = RugiLaba::namaBulan($rl->bulan) . ' ' . $rl->tahun;
        $filename = 'rugi-laba-' . $rl->cv->nama_cv . '-' . $rl->bulan . '-' . $rl->tahun . '.xlsx';

        return Excel::download(
            new RugiLabaExport($data, $rl->cv->nama_cv, $periode),
            $filename
        );
    }

    // ── Helper: Hitung data otomatis dari sistem ──────────────────────────────
    private function hitungOtomatis(int $cvId, int $bulan, int $tahun): array
    {
        $dari   = "{$tahun}-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
        $sampai = date('Y-m-t', strtotime($dari));
        $types  = ['gudang', 'direct', 'co_farm', 'rent_farm'];

        $pembelian = array_fill_keys($types, 0);
        $penjualan = array_fill_keys($types, 0);

        // Data dari PO
        $pakansPo = DB::table('po_penerima_pakan')
            ->select('po_penerima_pakan.jumlah_kg', 'po_penerima_pakan.ongkos_oa', 'po_penerima_pakan.harga_pt_sum', 'tujuan.type as tujuan_type')
            ->join('po_penerima', 'po_penerima.id', '=', 'po_penerima_pakan.po_penerima_id')
            ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->leftJoin('tujuan', 'tujuan.id', '=', 'po_penerima.tujuan_id')
            ->where('purchase_orders.cv_id', $cvId)
            ->whereDate('purchase_orders.tanggal_po', '>=', $dari)
            ->whereDate('purchase_orders.tanggal_po', '<=', $sampai)
            ->get();

        // Data dari Gudang Lansir
        $pakansGudang = DB::table('gudang_lansir_pakan')
            ->select('gudang_lansir_pakan.jumlah_kg', 'gudang_lansir_pakan.ongkos_oa', 'gudang_lansir_pakan.harga_pt_sum')
            ->join('gudang_lansir_penerima', 'gudang_lansir_penerima.id', '=', 'gudang_lansir_pakan.penerima_id')
            ->join('gudang_lansir_kendaraan', 'gudang_lansir_kendaraan.id', '=', 'gudang_lansir_penerima.kendaraan_id')
            ->join('gudang_lansir_header', 'gudang_lansir_header.id', '=', 'gudang_lansir_kendaraan.lansir_header_id')
            ->where('gudang_lansir_header.cv_id', $cvId)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '>=', $dari)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '<=', $sampai)
            ->get();

        // Gabungkan dan proses semua data
        $pakans = $pakansPo->merge($pakansGudang);

        foreach ($pakans as $p) {
            $type = in_array($p->tujuan_type ?? 'direct', $types) ? ($p->tujuan_type ?? 'direct') : 'direct';
            $pembelian[$type] += (float) $p->jumlah_kg * (float) ($p->ongkos_oa ?? 0);
            $penjualan[$type] += (float) $p->jumlah_kg * (float) ($p->harga_pt_sum ?? 0);
        }

        // Upah bongkar otomatis dari lansir gudang
        $upahGudang = DB::table('gudang_lansir_tim')
            ->join('gudang_lansir_penerima', 'gudang_lansir_penerima.id', '=', 'gudang_lansir_tim.penerima_id')
            ->join('gudang_lansir_kendaraan', 'gudang_lansir_kendaraan.id', '=', 'gudang_lansir_penerima.kendaraan_id')
            ->join('gudang_lansir_header', 'gudang_lansir_header.id', '=', 'gudang_lansir_kendaraan.lansir_header_id')
            ->where('gudang_lansir_header.cv_id', $cvId)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '>=', $dari)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '<=', $sampai)
            ->sum(DB::raw('gudang_lansir_tim.jumlah_kg * COALESCE(gudang_lansir_tim.upah_per_kg, 0)'));

        $upahPo = DB::table('po_lansir_tim')
            ->join('po_penerima_lansir', 'po_penerima_lansir.id', '=', 'po_lansir_tim.lansir_id')
            ->join('po_penerima', 'po_penerima.id', '=', 'po_penerima_lansir.po_penerima_id')
            ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->where('purchase_orders.cv_id', $cvId)
            ->whereDate('purchase_orders.tanggal_po', '>=', $dari)
            ->whereDate('purchase_orders.tanggal_po', '<=', $sampai)
            ->sum(DB::raw('COALESCE(po_lansir_tim.upah, 0)'));

        // Mobil lokal otomatis
        $mobilGudang = DB::table('gudang_lansir_pakan')
            ->join('gudang_lansir_penerima', 'gudang_lansir_penerima.id', '=', 'gudang_lansir_pakan.penerima_id')
            ->join('gudang_lansir_kendaraan', 'gudang_lansir_kendaraan.id', '=', 'gudang_lansir_penerima.kendaraan_id')
            ->join('gudang_lansir_header', 'gudang_lansir_header.id', '=', 'gudang_lansir_kendaraan.lansir_header_id')
            ->where('gudang_lansir_header.cv_id', $cvId)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '>=', $dari)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '<=', $sampai)
            ->sum(DB::raw('gudang_lansir_pakan.jumlah_kg * COALESCE(gudang_lansir_pakan.ongkos_oa, 0)'));

        $mobilPo = DB::table('po_lansir_mobil')
            ->join('po_penerima_lansir', 'po_penerima_lansir.id', '=', 'po_lansir_mobil.lansir_id')
            ->join('po_penerima', 'po_penerima.id', '=', 'po_penerima_lansir.po_penerima_id')
            ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->where('purchase_orders.cv_id', $cvId)
            ->whereDate('purchase_orders.tanggal_po', '>=', $dari)
            ->whereDate('purchase_orders.tanggal_po', '<=', $sampai)
            ->sum(DB::raw('COALESCE(po_lansir_mobil.berat, 0) * COALESCE(po_lansir_mobil.ongkos, 0)'));

        return [
            'pembelian'           => $pembelian,
            'penjualan'           => $penjualan,
            'totalPembelian'      => array_sum($pembelian),
            'totalPenjualan'      => array_sum($penjualan),
            'upahBongkarOtomatis' => (float) $upahGudang + (float) $upahPo,
            'mobilLokalOtomatis'  => (float) $mobilGudang + (float) $mobilPo,
            'types'               => $types,
        ];
    }
}
