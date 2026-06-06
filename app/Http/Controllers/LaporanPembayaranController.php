<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\GudangLansirHeader;
use App\Models\OaPayment;
use App\Models\PoKendaraan;
use App\Models\PoPenerima;
use App\Models\PurchaseOrder;
use App\Models\TransferPakanHeader;
use App\Services\RekapLansirService;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPembayaranController extends Controller
{
    use WithUserTujuan;
    public function __construct(
        private RekapLansirService $rekapLansirService,
    ) {}

    public function index(Request $request)
    {
        $activeCvId = session('active_cv');
        $tujuans = $this->getUserTujuan();
        $tujuanIds = $tujuans->pluck('id');

        $dari = $request->dari ?? now()->startOfYear()->format('Y-m-d');
        $sampai = $request->sampai ?? now()->format('Y-m-d');
        $cvId = $request->cv_id ?? $activeCvId;

        $poPenerimas = PoPenerima::with(['pakans', 'oaPayment', 'kendaraan.oaPayment', 'kendaraan.po', 'penerima'])
            ->whereIn('status', ['selesai', 'batal'])
            ->whereHas('kendaraan.po', function ($q) use ($cvId, $dari, $sampai) {
                $q->whereDate('tanggal_po', '>=', $dari)
                    ->whereDate('tanggal_po', '<=', $sampai);
                if ($cvId) {
                    $q->where('cv_id', $cvId);
                }
            })
            ->whereHas('penerima', function ($q) use ($tujuanIds) {
                $q->whereIn('tujuan_id', $tujuanIds);
            })
            ->get();

        $poKendaraan = PoKendaraan::with([
            'penerimas:id,po_kendaraan_id',
            'penerimas.pakans:id,po_penerima_id,jumlah_kg,ongkos_oa',
            'oaPayment',
        ])
            ->select('id', 'status', 'po_id')
            ->where('status', '!=', 'batal')
            ->whereHas('po', function ($q) use ($cvId, $dari, $sampai) {
                $q->whereDate('tanggal_po', '>=', $dari)
                    ->whereDate('tanggal_po', '<=', $sampai);
                if ($cvId) {
                    $q->where('cv_id', $cvId);
                }
            })
            ->whereHas('penerimas.penerima', function ($q) use ($tujuanIds) {
                $q->whereIn('tujuan_id', $tujuanIds);
            })
            ->get();
        // dd($poKendaraan->toArray());



        $oaTotalTagihan = $poKendaraan->sum(fn($po) => $po->total_tagihan_supplier);

        // Pembayaran OA banyak dicatat per kendaraan (po_kendaraan_id, po_penerima_id null).
        // Agregasi bayar: semua baris oa_payments terkait kendaraan / penerima dalam filter (tanpa duplikasi id).
        $kendaraanIds = $poKendaraan->pluck('id');
        $penerimaIds = $poPenerimas->pluck('id')->unique()->values();

        $oaPaymentRows = OaPayment::whereIn('po_kendaraan_id', $kendaraanIds)
            ->whereIn('tipe_pembayaran', ['oa', 'dp_supplier'])
            ->whereHas('kendaraan.penerimas.penerima', function ($q) use ($tujuanIds) {
                $q->whereIn('tujuan_id', $tujuanIds);
            })
            ->select('id', 'jumlah_bayar', 'status')
            ->get();

        $oaTotalBayar = (float) $oaPaymentRows->sum('jumlah_bayar');
        $oaTotalSisa  = max(0, $oaTotalTagihan - $oaTotalBayar);
        // Tagihan OA per kendaraan (sama dengan jumlah_tagihan di pembayaran kendaraan)
        $tagihanPerKendaraan = $poPenerimas->groupBy('po_kendaraan_id')->map(
            fn($rows) => (float) $rows->sum('total_oa')
        );

        $bayarPerKendaraan = OaPayment::query()
            ->whereIn('po_kendaraan_id', $kendaraanIds)
            ->whereIn('tipe_pembayaran', ['oa', 'dp_supplier'])
            ->whereHas('kendaraan.penerimas.penerima', function ($q) use ($tujuanIds) {
                $q->whereIn('tujuan_id', $tujuanIds);
            })
            ->get()
            ->groupBy('po_kendaraan_id')
            ->map(fn($rows) => (float) $rows->sum('jumlah_bayar'));

        // Hitung lunas PER KENDARAAN (bukan per penerima)
        $oaLunas = $poKendaraan->filter(function (PoKendaraan $k) use ($tagihanPerKendaraan, $bayarPerKendaraan) {
            // Cek apakah ada oaPayment per kendaraan dengan status lunas
            $hasLunasPayment = $k->oaPayment && $k->oaPayment->status === 'lunas';

            $kid = $k->id;
            $tagihan = (float) ($tagihanPerKendaraan[$kid] ?? 0);
            if ($tagihan <= 0) {
                return $hasLunasPayment;
            }
            $bayar = (float) ($bayarPerKendaraan[$kid] ?? 0);

            return $hasLunasPayment || ($bayar >= $tagihan);
        })->count();

        // Hitung total kendaraan untuk oaBelum
        $totalKendaraan = $poKendaraan->count();
        $oaBelum = $totalKendaraan - $oaLunas;

        // ── 2. Lansir Payment (dari PO) ───────────────────────────────
        $lansirPoQuery = PurchaseOrder::with([
            'cv',
            'lansirPaymentMobil',
            'lansirPaymentTim',
            'kendaraans.penerimas.pakans',
            'items.lansirRecords.mobils',
            'items.lansirRecords.tims',
        ])
            ->whereHas('kendaraans.penerimas.penerima', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->whereDate('tanggal_po', '>=', $dari)
            ->whereDate('tanggal_po', '<=', $sampai);

        if ($cvId) {
            $lansirPoQuery->where('cv_id', $cvId);
        }

        $lansirPos = $lansirPoQuery->get();

        // Hitung total tagihan lansir dari PO (total ongkos mobil + total upah tim)
        $lansirPoTotalOa = $lansirPos->sum(fn($po) => $this->rekapLansirService->getGrandTotalMobil($po) + $this->rekapLansirService->getGrandTotalTim($po));
        $lansirPoSudahBayarMobil = $lansirPos->sum(fn($po) => $this->rekapLansirService->getGrandTotalMobil($po));
        $lansirPoSudahBayarTim = $lansirPos->sum(fn($po) => $this->rekapLansirService->getGrandTotalTim($po));

        // ── 3. Lansir Gudang (informatif) ─────────────────────────────
        $gudangQuery = GudangLansirHeader::with([
            'cv',
            'gudang',
            'kendaraans.penerimas.pakans',
            'kendaraans.penerimas.tims',
        ])
            ->whereHas('kendaraans.penerimas', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->whereDate('tanggal_lansir', '>=', $dari)
            ->whereDate('tanggal_lansir', '<=', $sampai);

        if ($cvId) {
            $gudangQuery->where('cv_id', $cvId);
        }

        $gudangHeaders = $gudangQuery->get();

        $gudangTotalOa = $gudangHeaders->sum(
            fn($h) => $h->kendaraans->sum(
                fn($k) => $k->penerimas->sum(
                    fn($p) => $p->pakans->sum(fn($pk) => $pk->jumlah_kg * ($pk->ongkos_oa ?? 0))
                )
            )
        );
        $gudangTotalAngkut = $gudangHeaders->sum(
            fn($h) => $h->kendaraans->sum(
                fn($k) => $k->penerimas->sum(
                    fn($p) => $p->tims->sum('total_upah')
                )
            )
        );

        // ── 4. Transfer Pakan (informatif) ─────────────────────────────
        $transferQuery = TransferPakanHeader::with([
            'cv',
            'kendaraans.penerimas.pakans',
            'kendaraans.penerimas.tims',
        ])
            ->whereHas('kendaraans.penerimas', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->whereDate('tanggal_transfer', '>=', $dari)
            ->whereDate('tanggal_transfer', '<=', $sampai);

        if ($cvId) {
            $transferQuery->where('cv_id', $cvId);
        }

        $transferHeaders = $transferQuery->get();

        $transferTotalOa = $transferHeaders->sum(
            fn($h) => $h->kendaraans->sum(
                fn($k) => $k->penerimas->sum(
                    fn($p) => $p->pakans->sum(fn($pk) => $pk->jumlah_kg * ($pk->ongkos_oa ?? 0))
                )
            )
        );
        $transferTotalAngkut = $transferHeaders->sum(
            fn($h) => $h->kendaraans->sum(
                fn($k) => $k->penerimas->sum(
                    fn($p) => $p->tims->sum('total_upah')
                )
            )
        );

        // ── 5. Grafik: total tagihan per bulan (OA PO + Lansir Gudang + Transfer Pakan) ─
        $tahun = $request->tahun ?? now()->year;

        $chartOaPerBulan = DB::table('po_penerima_pakan as pk')
            ->join('po_penerima as p', 'p.id', '=', 'pk.po_penerima_id')
            ->join('po_kendaraan as k', 'k.id', '=', 'p.po_kendaraan_id')
            ->join('purchase_orders as po', 'po.id', '=', 'k.po_id')
            ->join('penerima as pen', 'pen.id', '=', 'p.penerima_id') // Join ke tabel penerima untuk tujuan
            ->selectRaw('MONTH(po.tanggal_po) as bulan, SUM(pk.jumlah_kg * pk.ongkos_oa) as total')
            ->whereYear('po.tanggal_po', $tahun)
            ->where('k.status', '!=', 'batal')
            ->whereIn('pen.tujuan_id', $tujuanIds) // Filter tujuan
            ->when($cvId, fn($q) => $q->where('po.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        // Hitung total OA dan total angkut secara terpisah untuk menghindari Cartesian product
        $chartGudangOaPerBulan = DB::table('gudang_lansir_header as h')
            ->join('gudang_lansir_kendaraan as k', 'k.lansir_header_id', '=', 'h.id')
            ->join('gudang_lansir_penerima as p', 'p.kendaraan_id', '=', 'k.id')
            ->leftJoin('gudang_lansir_pakan as pk', 'pk.penerima_id', '=', 'p.id')
            ->selectRaw('MONTH(h.tanggal_lansir) as bulan, SUM(COALESCE(pk.jumlah_kg, 0) * COALESCE(pk.ongkos_oa, 0)) as total')
            ->whereYear('h.tanggal_lansir', $tahun)
            ->whereIn('p.tujuan_id', $tujuanIds)
            ->when($cvId, fn($q) => $q->where('h.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $chartGudangAngkutPerBulan = DB::table('gudang_lansir_header as h')
            ->join('gudang_lansir_kendaraan as k', 'k.lansir_header_id', '=', 'h.id')
            ->join('gudang_lansir_penerima as p', 'p.kendaraan_id', '=', 'k.id')
            ->leftJoin('gudang_lansir_tim as tim', 'tim.penerima_id', '=', 'p.id')
            ->selectRaw('MONTH(h.tanggal_lansir) as bulan, SUM(COALESCE(tim.jumlah_kg, 0) * COALESCE(tim.upah_per_kg, 0)) as total')
            ->whereYear('h.tanggal_lansir', $tahun)
            ->whereIn('p.tujuan_id', $tujuanIds)
            ->when($cvId, fn($q) => $q->where('h.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        // Gabungkan kedua total
        $chartGudangPerBulan = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartGudangPerBulan[$m] = ($chartGudangOaPerBulan[$m] ?? 0) + ($chartGudangAngkutPerBulan[$m] ?? 0);
        }
        $chartGudangPerBulan = collect($chartGudangPerBulan);

        // Lakukan hal yang sama untuk Transfer Pakan
        $chartTransferOaPerBulan = DB::table('transfer_pakan_header as h')
            ->join('transfer_pakan_kendaraan as k', 'k.header_id', '=', 'h.id')
            ->join('transfer_pakan_penerima as p', 'p.kendaraan_id', '=', 'k.id')
            ->leftJoin('transfer_pakan_pakan as pk', 'pk.penerima_id', '=', 'p.id')
            ->selectRaw('MONTH(h.tanggal_transfer) as bulan, SUM(COALESCE(pk.jumlah_kg, 0) * COALESCE(pk.ongkos_oa, 0)) as total')
            ->whereYear('h.tanggal_transfer', $tahun)
            ->whereIn('p.tujuan_id', $tujuanIds)
            ->when($cvId, fn($q) => $q->where('h.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $chartTransferAngkutPerBulan = DB::table('transfer_pakan_header as h')
            ->join('transfer_pakan_kendaraan as k', 'k.header_id', '=', 'h.id')
            ->join('transfer_pakan_penerima as p', 'p.kendaraan_id', '=', 'k.id')
            ->leftJoin('transfer_pakan_tim as tim', 'tim.penerima_id', '=', 'p.id')
            ->selectRaw('MONTH(h.tanggal_transfer) as bulan, SUM(COALESCE(tim.jumlah_kg, 0) * COALESCE(tim.upah_per_kg, 0)) as total')
            ->whereYear('h.tanggal_transfer', $tahun)
            ->whereIn('p.tujuan_id', $tujuanIds)
            ->when($cvId, fn($q) => $q->where('h.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $chartTransferPerBulan = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartTransferPerBulan[$m] = ($chartTransferOaPerBulan[$m] ?? 0) + ($chartTransferAngkutPerBulan[$m] ?? 0);
        }
        $chartTransferPerBulan = collect($chartTransferPerBulan);

        $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartLabels = $namaBulan;
        $chartOa = [];
        $chartGudang = [];
        $chartTransfer = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartOa[] = (float) ($chartOaPerBulan[$m] ?? 0);
            $chartGudang[] = (float) ($chartGudangPerBulan[$m] ?? 0);
            $chartTransfer[] = (float) ($chartTransferPerBulan[$m] ?? 0);
        }

        // ── Dropdown filter ───────────────────────────────────────────
        $cvList = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $tahunList = PurchaseOrder::selectRaw('YEAR(tanggal_po) as tahun')
            ->groupBy('tahun')->orderBy('tahun', 'desc')->pluck('tahun');

        return view('pages.laporan.pembayaran', compact(
            'dari',
            'sampai',
            'cvId',
            'tahun',
            // Summary OA
            'oaTotalTagihan',
            'oaTotalBayar',
            'oaTotalSisa',
            'oaLunas',
            'oaBelum',
            // Summary Lansir PO
            'lansirPoTotalOa',
            'lansirPoSudahBayarMobil',
            'lansirPoSudahBayarTim',
            'lansirPos',
            // Summary Lansir Gudang
            'gudangTotalOa',
            'gudangTotalAngkut',
            'gudangHeaders',
            // Summary Transfer Pakan
            'transferTotalOa',
            'transferTotalAngkut',
            'transferHeaders',
            // Grafik
            'chartLabels',
            'chartOa',
            'chartGudang',
            'chartTransfer',
            // Filter
            'cvList',
            'tahunList'
        ));
    }
}
