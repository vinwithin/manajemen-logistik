<?php

namespace App\Http\Controllers;

use App\Models\OaPayment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PembayaranSupplierController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $supplierId = $request->supplier_id;
            $status     = $request->status;
            $from       = $request->from;
            $to         = $request->to;
            $activeCvId = session('active_cv');

            $query = OaPayment::with(['supplier', 'item.po.cv', 'item.tujuan'])
                ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($from, fn($q) => $q->whereDate('tanggal_bayar', '>=', $from))
                ->when($to,   fn($q) => $q->whereDate('tanggal_bayar', '<=', $to))
                ->when($activeCvId, fn($q) => $q->whereHas('item.po', fn($q) => $q->where('cv_id', $activeCvId)));

            return DataTables::of($query)
                ->addColumn('no_po', fn($q) => $q->item->po->no_po ?? '-')
                ->addColumn('cv_name', fn($q) => $q->item->po->cv?->nama_cv ?? '-')
                ->addColumn('no_polisi', fn($q) => $q->item->no_polisi ?? '-')
                ->addColumn('tujuan', fn($q) => $q->item->tujuan?->nama ?? '-')
                ->addColumn('supplier_nama', fn($q) => $q->supplier?->nama ?? '-')
                ->addColumn('sisa', fn($q) => max(0, $q->jumlah_tagihan - $q->jumlah_bayar))
                ->addColumn('status_badge', function ($q) {
                    $map = [
                        'pending' => ['secondary', 'Belum Bayar'],
                        'partial' => ['warning',   'Bayar Sebagian'],
                        'lunas'   => ['success',   'Lunas'],
                    ];
                    [$color, $label] = $map[$q->status] ?? ['secondary', $q->status];
                    return "<span class='badge bg-{$color}'>{$label}</span>";
                })
                ->addColumn('bukti', function ($q) {
                    if (!$q->bukti_bayar) return '-';
                    return "<a href='" . asset('storage/' . $q->bukti_bayar) . "' target='_blank'
                                class='btn btn-xs btn-outline-secondary'>
                                <i class='fa fa-file'></i> Lihat
                            </a>";
                })
                ->addIndexColumn()
                ->rawColumns(['status_badge', 'bukti'])
                ->make(true);
        }

        $suppliers = Supplier::orderBy('nama')->get();

        // Summary card
        $activeCvId = session('active_cv');
        $base = OaPayment::when($activeCvId, fn($q) => $q->whereHas('item.po', fn($q) => $q->where('cv_id', $activeCvId)));

        $summary = [
            'total_tagihan' => (clone $base)->sum('jumlah_tagihan'),
            'total_bayar'   => (clone $base)->sum('jumlah_bayar'),
            'total_sisa'    => (clone $base)->selectRaw('SUM(jumlah_tagihan - jumlah_bayar) as sisa')->value('sisa') ?? 0,
            'count_pending' => (clone $base)->where('status', 'pending')->count(),
            'count_partial' => (clone $base)->where('status', 'partial')->count(),
            'count_lunas'   => (clone $base)->where('status', 'lunas')->count(),
        ];

        return view('pages.keuangan.pembayaran.index', compact('suppliers', 'summary'));
    }
}
