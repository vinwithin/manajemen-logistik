<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPoController extends Controller
{
    public function index(Request $request)
    {
        $activeCvId = session('active_cv');

        // Filter params
        $dari    = $request->dari    ?? now()->startOfYear()->format('Y-m-d');
        $sampai  = $request->sampai  ?? now()->format('Y-m-d');
        $cvId    = $request->cv_id   ?? $activeCvId;
        $supplierId = $request->supplier_id ?? null;
        $tahun   = $request->tahun   ?? now()->year;

        // ── Summary cards ─────────────────────────────────────────────
        $baseQuery = PurchaseOrder::with(['kendaraans.penerimas.pakans'])
            ->whereBetween('tanggal_po', [$dari, $sampai]);

        if ($cvId) {
            $baseQuery->where('cv_id', $cvId);
        }

        if ($supplierId) {
            $baseQuery->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
        }

        $pos = $baseQuery->get();

        $totalPo       = $pos->count();
        $totalKendaraan = $pos->sum(fn($po) => $po->kendaraans->count());
        $totalVolume   = $pos->sum(fn($po) =>
            $po->kendaraans->sum(fn($k) =>
                $k->penerimas->sum('total_kg')
            )
        );
        $totalPtSum    = $pos->sum(fn($po) =>
            $po->kendaraans->sum(fn($k) =>
                $k->penerimas->sum('total_pt_sum')
            )
        );
        $totalOa       = $pos->sum(fn($po) =>
            $po->kendaraans->sum(fn($k) =>
                $k->penerimas->sum('total_oa')
            )
        );

        // ── Data grafik: volume per bulan dalam tahun yang dipilih ────
        $chartData = PurchaseOrder::select(
                DB::raw('MONTH(tanggal_po) as bulan'),
                DB::raw('SUM(po_kendaraan.jumlah_kg) as total_kg'),
                DB::raw('COUNT(DISTINCT purchase_orders.id) as total_po')
            )
            ->join('po_kendaraan', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
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
        $namaBulan    = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
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
            ])
            ->whereBetween('tanggal_po', [$dari, $sampai])
            ->orderBy('tanggal_po', 'desc');

        if ($cvId) {
            $tableQuery->where('cv_id', $cvId);
        }

        if ($supplierId) {
            $tableQuery->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
        }

        $tablePos = $tableQuery->paginate(25)->withQueryString();

        // ── Data untuk filter dropdown ────────────────────────────────
        $cvList      = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $supplierList = Supplier::orderBy('nama')->get();
        $tahunList   = PurchaseOrder::selectRaw('YEAR(tanggal_po) as tahun')
            ->groupBy('tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return view('pages.laporan.po', compact(
            'dari', 'sampai', 'cvId', 'supplierId', 'tahun',
            'totalPo', 'totalKendaraan', 'totalVolume', 'totalPtSum', 'totalOa',
            'chartLabels', 'chartVolume', 'chartPoCount',
            'tablePos',
            'cvList', 'supplierList', 'tahunList'
        ));
    }
}
