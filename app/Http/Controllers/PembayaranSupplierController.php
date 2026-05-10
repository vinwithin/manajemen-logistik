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
            $status = $request->status;
            $from = $request->from;
            $to = $request->to;
            $tipePembayaran = $request->tipe_pembayaran; // Filter by tipe
            $activeCvId = session('active_cv');

            $query = OaPayment::with([
                'supplier',
                'penerima.kendaraan.po.cv', // Untuk tipe 'oa'
                'penerima.tujuan',
                'kendaraan.po.cv', // Untuk tipe 'dp_supplier'
            ])
                ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($tipePembayaran, fn ($q) => $q->where('tipe_pembayaran', $tipePembayaran))
                ->when($from, fn ($q) => $q->whereDate('tanggal_bayar', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('tanggal_bayar', '<=', $to))
                ->when($activeCvId, function ($q) use ($activeCvId) {
                    // Filter by CV untuk kedua tipe pembayaran
                    $q->where(function ($q) use ($activeCvId) {
                        $q->whereHas('penerima.kendaraan.po', fn ($q) => $q->where('cv_id', $activeCvId))
                            ->orWhereHas('kendaraan.po', fn ($q) => $q->where('cv_id', $activeCvId));
                    });
                })
                ->orderBy('tanggal_bayar', 'desc')
                ->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addColumn('tipe', function ($q) {
                    $badges = [
                        'oa' => ['info', 'Pembayaran OA'],
                        'dp_supplier' => ['warning', 'DP Supplier'],
                    ];
                    [$color, $label] = $badges[$q->tipe_pembayaran] ?? ['secondary', $q->tipe_pembayaran];

                    return "<span class='badge bg-{$color}'>{$label}</span>";
                })
                ->addColumn('no_po', function ($q) {
                    // Untuk tipe 'dp_supplier', ambil dari kendaraan.po
                    // Untuk tipe 'oa', ambil dari penerima.kendaraan.po
                    if ($q->tipe_pembayaran === 'dp_supplier' && $q->kendaraan) {
                        return $q->kendaraan->po->no_po ?? '-';
                    }

                    return $q->penerima?->kendaraan?->po->no_po ?? '-';
                })
                ->addColumn('cv_name', function ($q) {
                    if ($q->tipe_pembayaran === 'dp_supplier' && $q->kendaraan) {
                        return $q->kendaraan->po->cv?->nama_cv ?? '-';
                    }

                    return $q->penerima?->kendaraan?->po->cv?->nama_cv ?? '-';
                })
                ->addColumn('no_polisi', function ($q) {
                    if ($q->tipe_pembayaran === 'dp_supplier' && $q->kendaraan) {
                        return $q->kendaraan->no_polisi ?? '-';
                    }

                    return $q->penerima?->kendaraan?->no_polisi ?? '-';
                })
                ->addColumn('tujuan', function ($q) {
                    // Tujuan hanya ada untuk tipe 'oa' (pembayaran ke penerima)
                    if ($q->tipe_pembayaran === 'oa') {
                        return $q->penerima?->tujuan?->nama ?? '-';
                    }

                    return '<span class="text-muted">—</span>';
                })
                ->addColumn('supplier_nama', fn ($q) => $q->supplier?->nama ?? '-')
                ->addColumn('sisa', fn ($q) => max(0, $q->jumlah_tagihan - $q->jumlah_bayar))
                ->editColumn('tanggal_bayar', fn ($q) => $q->tanggal_bayar ? $q->tanggal_bayar->format('d/m/Y') : '-')
                ->editColumn('metode_bayar', fn ($q) => $q->metode_bayar ? ucfirst($q->metode_bayar) : '-')
                ->addColumn('status_badge', function ($q) {
                    $map = [
                        'pending' => ['secondary', 'Belum Bayar'],
                        'partial' => ['warning',   'Bayar Sebagian'],
                        'lunas' => ['success',   'Lunas'],
                    ];
                    [$color, $label] = $map[$q->status] ?? ['secondary', $q->status];

                    return "<span class='badge bg-{$color}'>{$label}</span>";
                })
                ->addColumn('bukti', function ($q) {
                    if (! $q->bukti_bayar) {
                        return '-';
                    }

                    return "<a href='".asset('storage/'.$q->bukti_bayar)."' target='_blank'
                                class='btn btn-xs btn-outline-secondary'>
                                <i class='fa fa-file'></i> Lihat
                            </a>";
                })
                ->addIndexColumn()
                ->rawColumns(['tipe', 'status_badge', 'bukti', 'tujuan'])
                ->make(true);
        }

        $suppliers = Supplier::orderBy('nama')->get();

        // Summary card
        $activeCvId = session('active_cv');
        $base = OaPayment::when($activeCvId, function ($q) use ($activeCvId) {
            $q->where(function ($q) use ($activeCvId) {
                $q->whereHas('penerima.kendaraan.po', fn ($q) => $q->where('cv_id', $activeCvId))
                    ->orWhereHas('kendaraan.po', fn ($q) => $q->where('cv_id', $activeCvId));
            });
        });

        $summary = [
            'total_tagihan' => (clone $base)->sum('jumlah_tagihan'),
            'total_bayar' => (clone $base)->sum('jumlah_bayar'),
            'total_sisa' => (clone $base)->selectRaw('SUM(jumlah_tagihan - jumlah_bayar) as sisa')->value('sisa') ?? 0,
            'count_pending' => (clone $base)->where('status', 'pending')->count(),
            'count_partial' => (clone $base)->where('status', 'partial')->count(),
            'count_lunas' => (clone $base)->where('status', 'lunas')->count(),
            // Summary by tipe
            'count_oa' => (clone $base)->where('tipe_pembayaran', 'oa')->count(),
            'count_dp' => (clone $base)->where('tipe_pembayaran', 'dp_supplier')->count(),
            'total_dp' => (clone $base)->where('tipe_pembayaran', 'dp_supplier')->sum('jumlah_bayar'),
        ];

        return view('pages.keuangan.pembayaran.index', compact('suppliers', 'summary'));
    }
}
