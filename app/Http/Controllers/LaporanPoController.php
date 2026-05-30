<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\GudangLansirHeader;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPoController extends Controller
{
    use WithUserTujuan;
    public function index(Request $request)
    {
        $activeCvId = session('active_cv');

        // Filter params
        $dari    = $request->dari    ?? now()->startOfYear()->format('Y-m-d');
        $sampai  = $request->sampai  ?? now()->format('Y-m-d');
        $cvId    = $request->cv_id   ?? $activeCvId;
        $supplierId = $request->supplier_id ?? null;
        $tahun   = $request->tahun   ?? now()->year;
        $tujuans = $this->getUserTujuan();


        $baseQuery = PurchaseOrder::with([
            'kendaraans' => function ($q) {
                $q->where('status', '!=', 'batal')
                    ->with(['penerimas.pakans', 'penerimas.penerima']);
            },
        ])
            ->where(function ($q) use ($tujuans) {
                $q->whereHas('kendaraans.penerimas.penerima', function ($q) use ($tujuans) {
                    $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                })
                    ->orWhereDoesntHave('kendaraans.penerimas')
                    ->orWhereHas('kendaraans.penerimas', function ($q) {
                        $q->whereDoesntHave('penerima');
                    });
            })
            ->whereBetween('tanggal_po', [$dari, $sampai]);

        if ($cvId) {
            $baseQuery = $baseQuery->where('cv_id', $cvId);
        }

        if ($supplierId) {
            $baseQuery = $baseQuery->whereHas(
                'kendaraans',
                fn($q) => $q->where('supplier_id', $supplierId)
            );
        }

        $pos = $baseQuery->get();

        $tujuanIds = $tujuans->pluck('id');


        // Data PO
        $totalPoPo       = $pos->count();
        $totalKendaraanPo = $pos->sum(
            fn($po) =>
            $po->kendaraans->where('status', '!=', 'batal')->filter(
                fn($k) =>
                $k->penerimas->contains(
                    fn($p) =>
                    $tujuanIds->contains($p->penerima?->tujuan_id)
                )
            )->count()
        );
        $totalVolumePo   = $pos->sum(
            fn($po) =>
            $po->kendaraans->sum(
                fn($k) =>
                $k->penerimas
                    ->filter(fn($p) => $tujuanIds->contains($p->penerima?->tujuan_id))
                    ->sum('total_kg')
            )
        );
        $totalPtSumPo    = $pos->sum(
            fn($po) =>
            $po->kendaraans->sum(
                fn($k) =>
                $k->penerimas
                    ->filter(fn($p) => $tujuanIds->contains($p->penerima?->tujuan_id))
                    ->sum('total_pt_sum')
            )
        );
        $totalOaPo       = $pos->sum(
            fn($po) =>
            $po->kendaraans->sum(
                fn($k) =>
                $k->penerimas
                    ->filter(fn($p) => $tujuanIds->contains($p->penerima?->tujuan_id))
                    ->sum('total_oa')
            )
        );

        // Data Gudang Lansir
        $gudangQuery = GudangLansirHeader::with(['kendaraans.penerimas.pakans', 'kendaraans.penerimas.tims'])
            ->whereHas('kendaraans.penerimas', function ($q) use ($tujuanIds) {
                $q->whereIn('tujuan_id', $tujuanIds);
            })
            ->whereBetween('tanggal_lansir', [$dari, $sampai]);

        if ($cvId) {
            $gudangQuery->where('cv_id', $cvId);
        }

        $gudangLansirs = $gudangQuery->get();

        $totalPoGudang       = $gudangLansirs->count();
        $totalKendaraanGudang = $gudangLansirs->sum(
            fn($gl) =>
            $gl->kendaraans->filter(
                fn($k) =>
                $k->penerimas->contains(
                    fn($p) =>
                    $tujuanIds->contains($p->tujuan_id)
                )
            )->count()
        );

        $totalVolumeGudang   = $gudangLansirs->sum(
            fn($gl) =>
            $gl->kendaraans->sum(
                fn($k) =>
                $k->penerimas
                    ->filter(fn($p) => $tujuanIds->contains($p->tujuan_id))
                    ->sum('total_kg')
            )
        );
        $totalPtSumGudang = $gudangLansirs->sum(
            fn($gl) =>
            $gl->kendaraans->sum(
                fn($k) =>
                $k->penerimas
                    ->filter(fn($p) => $tujuanIds->contains($p->tujuan_id))
                    ->sum(fn($p) => $p->pakans->sum(fn($pakan) => $pakan->jumlah_kg * $pakan->harga_pt_sum))
            )
        );

        $totalOaGudang       = $gudangLansirs->sum(
            fn($gl) =>
            $gl->kendaraans->sum(
                fn($k) =>
                $k->penerimas
                    ->filter(fn($p) => $tujuanIds->contains($p->tujuan_id))
                    ->sum(fn($p) => $p->pakans->sum(fn($pakan) => $pakan->jumlah_kg * $pakan->ongkos_oa))
            )
        );

        // Jumlahkan semua
        $totalPo       = $totalPoPo + $totalPoGudang;
        $totalKendaraan = $totalKendaraanPo + $totalKendaraanGudang;
        $totalVolume   = $totalVolumePo + $totalVolumeGudang;
        $totalPtSum    = $totalPtSumPo + $totalPtSumGudang;
        $totalOa       = $totalOaPo + $totalOaGudang;

        // ── Data grafik: volume per bulan dalam tahun yang dipilih ────
        $chartData = PurchaseOrder::select(
            DB::raw('MONTH(tanggal_po) as bulan'),
            DB::raw('SUM(po_penerima_pakan.jumlah_kg) as total_kg'),
            DB::raw('COUNT(DISTINCT purchase_orders.id) as total_po')
        )
            ->join('po_kendaraan', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->join('po_penerima', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->join('po_penerima_pakan', 'po_penerima.id', '=', 'po_penerima_pakan.po_penerima_id')
            ->join('penerima', 'po_penerima.penerima_id', '=', 'penerima.id')
            ->where('po_kendaraan.status', '!=', 'batal')
            ->whereIn('penerima.tujuan_id', $tujuanIds)
            ->whereYear('tanggal_po', $tahun)
            ->when($cvId, fn($q) => $q->where('cv_id', $cvId))
            ->groupBy(DB::raw('MONTH(tanggal_po)'))
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        // Isi semua 12 bulan (bulan tanpa data = 0)
        $chartLabels  = [];
        $chartVolume  = [];
        $chartPoCount = [];
        $namaBulan    = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        for ($m = 1; $m <= 12; $m++) {
            $chartLabels[]  = $namaBulan[$m - 1];
            $chartVolume[]  = (float) ($chartData[$m]->total_kg ?? 0);
            $chartPoCount[] = (int)   ($chartData[$m]->total_po ?? 0);
        }

        // ── Tabel rekap PO ────────────────────────────────────────────
        $tableQuery = PurchaseOrder::with([
            'cv',
            'kendaraans.supplier',
            'kendaraans.penerimas.pakans',
            'kendaraans.penerimas.penerima',
        ])
            ->whereHas('kendaraans.penerimas.penerima', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->whereBetween('tanggal_po', [$dari, $sampai])
            ->orderBy('tanggal_po', 'desc');

        if ($cvId) {
            $tableQuery->where('cv_id', $cvId);
        }

        if ($supplierId) {
            $tableQuery->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
        }

        $tablePos = $tableQuery->paginate(5)->withQueryString();

        // ── Data untuk filter dropdown ────────────────────────────────
        $cvList      = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $supplierList = Supplier::orderBy('nama')->get();
        $tahunList   = PurchaseOrder::selectRaw('YEAR(tanggal_po) as tahun')
            ->groupBy('tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return view('pages.laporan.po', compact(
            'dari',
            'sampai',
            'cvId',
            'supplierId',
            'tahun',
            'totalPo',
            'totalKendaraan',
            'totalVolume',
            'totalPtSum',
            'totalOa',
            'chartLabels',
            'chartVolume',
            'chartPoCount',
            'tablePos',
            'cvList',
            'supplierList',
            'tahunList'
        ));
    }
}
