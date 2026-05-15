<?php

namespace App\Http\Controllers;

use App\Models\OaPayment;
use App\Models\Supplier;
use App\Models\PoPenerima;
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
                    // Coba dari kendaraan terlebih dahulu (untuk dp_supplier)
                    if ($q->kendaraan && $q->kendaraan->po) {
                        return $q->kendaraan->po->no_po;
                    }
                    // Lalu coba dari penerima (untuk oa)
                    if ($q->penerima && $q->penerima->kendaraan && $q->penerima->kendaraan->po) {
                        return $q->penerima->kendaraan->po->no_po;
                    }
                    return '<span class="text-muted small">—</span>';
                })
                ->addColumn('cv_name', function ($q) {
                    if ($q->kendaraan && $q->kendaraan->po && $q->kendaraan->po->cv) {
                        return $q->kendaraan->po->cv->nama_cv;
                    }
                    if ($q->penerima && $q->penerima->kendaraan && $q->penerima->kendaraan->po && $q->penerima->kendaraan->po->cv) {
                        return $q->penerima->kendaraan->po->cv->nama_cv;
                    }
                    return '<span class="text-muted small">—</span>';
                })
                ->addColumn('no_polisi', function ($q) {
                    if ($q->kendaraan && $q->kendaraan->no_polisi) {
                        return $q->kendaraan->no_polisi;
                    }
                    if ($q->penerima && $q->penerima->kendaraan && $q->penerima->kendaraan->no_polisi) {
                        return $q->penerima->kendaraan->no_polisi;
                    }
                    return '<span class="text-muted small">—</span>';
                })
                ->addColumn('tujuan', function ($q) {
                    if ($q->kendaraan && $q->kendaraan->tujuan) {
                        return $q->kendaraan->tujuan->nama;
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
                ->rawColumns(['tipe', 'status_badge', 'bukti', 'tujuan', 'no_po', 'cv_name', 'no_polisi'])
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
       
         $poPenerimas = PoPenerima::with(['pakans', 'oaPayment', 'kendaraan.oaPayment', 'kendaraan.po'])
            ->whereIn('status', ['selesai', 'batal'])
            ->whereHas('kendaraan.po', function ($q) use ($activeCvId) {
                if ($activeCvId) {
                    $q->where('cv_id', $activeCvId);
                }
            })
            ->get();

         $kendaraanIds = $poPenerimas->pluck('po_kendaraan_id')->unique()->filter()->values();
        $penerimaIds = $poPenerimas->pluck('id')->unique()->values();
         $oaPaymentRows = OaPayment::query()
            ->whereIn('tipe_pembayaran', ['oa', 'dp_supplier'])
            ->where(function ($q) use ($kendaraanIds, $penerimaIds) {
                $q->whereIn('po_kendaraan_id', $kendaraanIds)
                    ->orWhereIn('po_penerima_id', $penerimaIds);
            })
            ->get()
            ->unique('id');
            
        $oaTotalTagihan = (float) $poPenerimas->sum('total_oa');
        $oaTotalBayar = (float) $oaPaymentRows->sum('jumlah_bayar');
        $oaTotalSisa = max(0, $oaTotalTagihan - $oaTotalBayar);
        $summary = [
            'total_tagihan' => $oaTotalTagihan,
            // 'total_tagihan' => (clone $base)->sum('jumlah_tagihan'),
            'total_bayar' => (clone $base)->sum('jumlah_bayar'),
            'total_sisa' => $oaTotalSisa,
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
