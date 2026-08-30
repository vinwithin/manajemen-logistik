<?php

namespace App\Http\Controllers;

use App\Exports\RugiLabaExport;
use App\Models\Cv;
use App\Models\RugiLaba;
use App\Models\RugiLabaHarian;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RekapRugiLabaController extends Controller
{
    use WithUserTujuan;

    private const VOUCHER_CUTOFF_DATE = '2026-08-1';

    // ── Daftar per CV ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $cvList = Cv::orderBy('nama_cv')->get();
        $cvId = $request->cv_id ?? session('active_cv');
        $tahun = (int) ($request->tahun ?? now()->year);

        $records = collect();
        $cv = null;
        $summary = []; // ringkasan per bulan untuk tabel

        if ($cvId) {
            $cv = Cv::find($cvId);
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

                $labaKotor = $auto['totalPenjualan'] - $auto['totalPembelian'];
                $pph21 = $labaKotor > 0 ? $labaKotor * 0.005 : 0;
                $labaBersih = $labaKotor - $totalBiaya - $pph21 - $auto['voucher'];

                $summary[$rl->id] = [
                    'totalPembelian' => $auto['totalPembelian'],
                    'totalPenjualan' => $auto['totalPenjualan'],
                    'totalBiaya' => $totalBiaya,
                    'labaKotor' => $labaKotor,
                    'labaBersih' => $labaBersih,
                ];
            }
        }

        return view('pages.keuangan.rugi-laba.index', compact(
            'cvList',
            'cvId',
            'tahun',
            'cv',
            'records',
            'summary'
        ));
    }

    // ── Form Create ───────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $cvList = Cv::orderBy('nama_cv')->get();
        $cvId = $request->cv_id ?? session('active_cv');
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);

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
            'cvList',
            'cvId',
            'bulan',
            'tahun',
            'cv',
            'autoData'
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
            'gaji',
            'biaya_sewa',
            'atk',
            'pembayaran_supplier_lintas',
            'pembayaran_mobil_lokal',
            'sharing_fee',
            'sharing_profit',
            'perjalanan_dinas',
            'entertain',
            'adm_bank',
            'upah_bongkar',
            'upah_muat',
            'upah_bongkar_muat',
            'biaya_lain_lain',
            'bbm',
            'listrik',
            'pdam',
            'lingkungan',
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = (float) ($request->input($f, 0));
        }
        $data['catatan'] = $request->catatan;
        $data['periode_label'] = RugiLaba::namaBulan((int) $request->bulan).' '.$request->tahun;
        $data['created_by'] = Auth::id();

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
        $rl = RugiLaba::with('cv')->findOrFail($id);
        $data = $this->hitungOtomatis($rl->cv_id, $rl->bulan, $rl->tahun);
        $data['rl'] = $rl;

        // Kalkulasi final
        $data['totalBiayaOperasional'] = $rl->total_biaya_operasional
            + $data['upahBongkarOtomatis']
            + $data['mobilLokalOtomatis'];

        $data['labaKotor'] = $data['totalPenjualan'] - $data['totalPembelian'];
        $data['pph21'] = $data['labaKotor'] > 0 ? $data['labaKotor'] * 0.005 : 0;
        $data['labaBersih'] = $data['labaKotor'] - $data['totalBiayaOperasional'] - $data['pph21'] - $data['voucher'];

        return view('pages.keuangan.rugi-laba.show', compact('rl', 'data'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(string $id)
    {
        $rl = RugiLaba::with('cv')->findOrFail($id);
        $cvList = Cv::orderBy('nama_cv')->get();
        $autoData = $this->hitungOtomatis($rl->cv_id, $rl->bulan, $rl->tahun);

        return view('pages.keuangan.rugi-laba.create', [
            'cvList' => $cvList,
            'cvId' => $rl->cv_id,
            'bulan' => $rl->bulan,
            'tahun' => $rl->tahun,
            'cv' => $rl->cv,
            'autoData' => $autoData,
            'rl' => $rl,
        ]);
    }

    // ── Input Harian ──────────────────────────────────────────────────────────
    public function harian(string $id)
    {
        $rl = RugiLaba::with('cv')->findOrFail($id);
        $labels = RugiLabaHarian::labelBiaya();

        // Entri harian dikelompokkan per tanggal
        $entries = RugiLabaHarian::where('rugi_laba_id', $rl->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($e) => $e->tanggal->format('Y-m-d'));

        // Total per kode biaya
        $totals = $rl->totalHarian();

        return view('pages.keuangan.rugi-laba.harian', compact('rl', 'labels', 'entries', 'totals'));
    }

    public function storeHarian(Request $request, string $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'kode_biaya' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $rl = RugiLaba::findOrFail($id);

        RugiLabaHarian::create([
            'rugi_laba_id' => $rl->id,
            'tanggal' => $request->tanggal,
            'kode_biaya' => $request->kode_biaya,
            'keterangan' => $request->keterangan,
            'nominal' => $request->nominal,
            'created_by' => Auth::id(),
        ]);

        // Sync total ke kolom rugi_laba (agar laporan tetap konsisten)
        $this->syncTotalHarianKeRugiLaba($rl);

        return redirect()->back()->with('success', 'Entri harian berhasil disimpan.');
    }

    public function destroyHarian(string $harianId)
    {
        $harian = RugiLabaHarian::findOrFail($harianId);
        $rl = $harian->rugLaba;
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
    public function export(string $id)
    {
        $rl = RugiLaba::with('cv')->findOrFail($id);
        $data = $this->hitungOtomatis($rl->cv_id, $rl->bulan, $rl->tahun);
        $data['rl'] = $rl;

        $data['totalBiayaOperasional'] = $rl->total_biaya_operasional
            + $data['upahBongkarOtomatis']
            + $data['mobilLokalOtomatis'];

        $data['labaKotor'] = $data['totalPenjualan'] - $data['totalPembelian'];
        $data['pph21'] = $data['labaKotor'] > 0 ? $data['labaKotor'] * 0.005 : 0;
        $data['labaBersih'] = $data['labaKotor'] - $data['totalBiayaOperasional'] - $data['pph21'] - $data['voucher'];

        $periode = RugiLaba::namaBulan($rl->bulan).' '.$rl->tahun;
        $filename = 'rugi-laba-'.$rl->cv->nama_cv.'-'.$rl->bulan.'-'.$rl->tahun.'.xlsx';

        return Excel::download(
            new RugiLabaExport($data, $rl->cv->nama_cv, $periode),
            $filename
        );
    }

    // ── Helper: Hitung data otomatis dari sistem ──────────────────────────────
    public function hitungOtomatis(int $cvId, int $bulan, int $tahun): array
    {
        $tujuans = $this->getUserTujuan();

        $dari = "{$tahun}-".str_pad($bulan, 2, '0', STR_PAD_LEFT).'-01';
        $sampai = date('Y-m-t', strtotime($dari));
        $types = ['gudang', 'direct', 'co_farm', 'rent_farm', 'tr_kerinci', 'gudang_ke_peternak', 'transfer_pakan'];

        $pembelian = array_fill_keys($types, 0);
        $penjualan = array_fill_keys($types, 0);

        // Data dari PO
        $pakansPo = DB::table('po_penerima_pakan')
            ->select(
                'po_penerima_pakan.jumlah_kg',
                'po_penerima_pakan.ongkos_oa',
                'po_penerima_pakan.harga_pt_sum',
                'purchase_orders.tanggal_po as tanggal_transaksi',
                'tujuan.type as tujuan_type'
            )
            ->join('po_penerima', 'po_penerima.id', '=', 'po_penerima_pakan.po_penerima_id')
            ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->leftJoin('penerima', 'penerima.id', '=', 'po_penerima.penerima_id')
            ->leftJoin('tujuan', 'tujuan.id', '=', 'penerima.tujuan_id')
            ->where('po_kendaraan.status', '!=', 'batal')
            ->whereIn('penerima.tujuan_id', $tujuans->pluck('id'))
            ->where('purchase_orders.cv_id', $cvId)
            ->whereDate('purchase_orders.tanggal_po', '>=', $dari)
            ->whereDate('purchase_orders.tanggal_po', '<=', $sampai)
            ->get();

        // Data dari Gudang Lansir
        $pakansGudang = DB::table('gudang_lansir_pakan')
            ->select(
                'gudang_lansir_pakan.jumlah_kg',
                'gudang_lansir_pakan.ongkos_oa',
                'gudang_lansir_pakan.harga_pt_sum',
                'gudang_lansir_header.tanggal_lansir as tanggal_transaksi',
                DB::raw("'gudang_ke_peternak' as tujuan_type")
            )
            ->join('gudang_lansir_penerima', 'gudang_lansir_penerima.id', '=', 'gudang_lansir_pakan.penerima_id')
            ->join('gudang_lansir_kendaraan', 'gudang_lansir_kendaraan.id', '=', 'gudang_lansir_penerima.kendaraan_id')
            ->join('gudang_lansir_header', 'gudang_lansir_header.id', '=', 'gudang_lansir_kendaraan.lansir_header_id')
            ->leftJoin('tujuan', 'tujuan.id', '=', 'gudang_lansir_penerima.tujuan_id')
            ->where('gudang_lansir_header.cv_id', $cvId)
            ->whereIn('gudang_lansir_penerima.tujuan_id', $tujuans->pluck('id'))
            ->whereDate('gudang_lansir_header.tanggal_lansir', '>=', $dari)
            ->whereDate('gudang_lansir_header.tanggal_lansir', '<=', $sampai)
            ->get();

        // Data dari Transfer Pakan
        $pakansTransfer = DB::table('transfer_pakan_pakan')
            ->select(
                'transfer_pakan_pakan.jumlah_kg',
                'transfer_pakan_pakan.ongkos_oa',
                'transfer_pakan_pakan.harga_pt_sum',
                'transfer_pakan_header.tanggal_transfer as tanggal_transaksi',
                DB::raw("'transfer_pakan' as tujuan_type")
            )
            ->join('transfer_pakan_penerima', 'transfer_pakan_penerima.id', '=', 'transfer_pakan_pakan.penerima_id')
            ->join('transfer_pakan_kendaraan', 'transfer_pakan_kendaraan.id', '=', 'transfer_pakan_penerima.kendaraan_id')
            ->join('transfer_pakan_header', 'transfer_pakan_header.id', '=', 'transfer_pakan_kendaraan.header_id')
            ->leftJoin('tujuan', 'tujuan.id', '=', 'transfer_pakan_penerima.tujuan_id')
            ->where('transfer_pakan_header.cv_id', $cvId)
            ->whereIn('transfer_pakan_penerima.tujuan_id', $tujuans->pluck('id'))
            ->whereDate('transfer_pakan_header.tanggal_transfer', '>=', $dari)
            ->whereDate('transfer_pakan_header.tanggal_transfer', '<=', $sampai)
            ->get();

        $pakans = $pakansPo->merge($pakansGudang)->merge($pakansTransfer);

        $pakansVoucher = $pakans
            ->filter(fn ($p) => date('Y-m-d', strtotime($p->tanggal_transaksi)) < self::VOUCHER_CUTOFF_DATE);
        $voucherPenjualan = $pakansVoucher
            ->sum(fn ($p) => (float) $p->jumlah_kg * (float) ($p->harga_pt_sum ?? 0));
        $voucherPembelian = $pakansVoucher
            ->sum(fn ($p) => (float) $p->jumlah_kg * (float) ($p->ongkos_oa ?? 0));
        $voucherLabaKotor = $voucherPenjualan - $voucherPembelian;

        // Pembelian: dari PO, gudang lansir, dan transfer pakan
        foreach ($pakans as $p) {
            $type = in_array($p->tujuan_type ?? 'direct', $types) ? ($p->tujuan_type ?? 'direct') : 'direct';
            $pembelian[$type] += (float) $p->jumlah_kg * (float) ($p->ongkos_oa ?? 0);
        }

        // Penjualan: dari PO, gudang lansir, dan transfer pakan
        foreach ($pakans as $p) {
            $type = in_array($p->tujuan_type ?? 'direct', $types) ? ($p->tujuan_type ?? 'direct') : 'direct';
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

        $mobilPo = DB::table('po_lansir_mobil')
            ->join('po_penerima_lansir', 'po_penerima_lansir.id', '=', 'po_lansir_mobil.lansir_id')
            ->join('po_penerima', 'po_penerima.id', '=', 'po_penerima_lansir.po_penerima_id')
            ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->where('purchase_orders.cv_id', $cvId)
            ->whereDate('purchase_orders.tanggal_po', '>=', $dari)
            ->whereDate('purchase_orders.tanggal_po', '<=', $sampai)
            ->sum(DB::raw('COALESCE(po_lansir_mobil.berat, 0) * COALESCE(po_lansir_mobil.ongkos, 0)'));

        $upahPo = DB::table('po_lansir_tim')
            ->join('po_penerima_lansir', 'po_penerima_lansir.id', '=', 'po_lansir_tim.lansir_id')
            ->join('po_penerima', 'po_penerima.id', '=', 'po_penerima_lansir.po_penerima_id')
            ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->where('purchase_orders.cv_id', $cvId)
            ->whereDate('purchase_orders.tanggal_po', '>=', $dari)
            ->whereDate('purchase_orders.tanggal_po', '<=', $sampai)
            ->sum(DB::raw('COALESCE(po_lansir_tim.berat, 0) * COALESCE(po_lansir_tim.upah, 0)'));

        $upahTransfer = DB::table('transfer_pakan_tim')
            ->join('transfer_pakan_penerima', 'transfer_pakan_penerima.id', '=', 'transfer_pakan_tim.penerima_id')
            ->join('transfer_pakan_kendaraan', 'transfer_pakan_kendaraan.id', '=', 'transfer_pakan_penerima.kendaraan_id')
            ->join('transfer_pakan_header', 'transfer_pakan_header.id', '=', 'transfer_pakan_kendaraan.header_id')
            ->where('transfer_pakan_header.cv_id', $cvId)
            ->whereDate('transfer_pakan_header.tanggal_transfer', '>=', $dari)
            ->whereDate('transfer_pakan_header.tanggal_transfer', '<=', $sampai)
            ->sum(DB::raw('transfer_pakan_tim.jumlah_kg * COALESCE(transfer_pakan_tim.upah_per_kg, 0)'));

        $totalPembelian = array_sum($pembelian);
        $totalPenjualan = array_sum($penjualan);
        $voucherAktif = $dari < self::VOUCHER_CUTOFF_DATE;

        return [
            'pembelian' => $pembelian,
            'penjualan' => $penjualan,
            'totalPembelian' => $totalPembelian,
            'totalPenjualan' => $totalPenjualan,
            'voucher' => $voucherAktif && $voucherLabaKotor > 0 ? $voucherLabaKotor * 0.005 : 0,
            'voucherAktif' => $voucherAktif,
            'voucherCutoffDate' => self::VOUCHER_CUTOFF_DATE,
            'upahBongkarOtomatis' => (float) $upahGudang + (float) $upahPo + (float) $upahTransfer,
            'mobilLokalOtomatis' => (float) $mobilPo,
            'types' => $types,
        ];
    }
}
