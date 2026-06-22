<?php

namespace App\Http\Controllers;

use App\Exports\PembayaranSupplierExport;
use App\Models\OaPayment;
use App\Models\PoKendaraan;
use App\Models\Supplier;
use App\Models\PoPenerima;
use App\Models\PurchaseOrder;
use App\Traits\WithUserTujuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class PembayaranSupplierController extends Controller
{
    use WithUserTujuan;

    public function exportPdfConfirm(Request $request)
    {
        $suppliers = Supplier::orderBy('nama')->get();
        $tujuans = $this->getUserTujuan();
        $from = $request->from;
        $to = $request->to;
        $supplierIds = $request->input('supplier_ids', []);
        $tujuanId = $request->tujuan_id;
        $tipePembayaran = $request->tipe_pembayaran;
        $paymentCount = null;

        if ($from && $to) {
            $paymentCount = $this->filteredKendaraanQuery($request)->count();
        }

        return view('pages.keuangan.pembayaran.export-pdf-confirm', compact(
            'suppliers',
            'tujuans',
            'from',
            'to',
            'supplierIds',
            'tujuanId',
            'tipePembayaran',
            'paymentCount'
        ));
    }

    public function exportPdf(Request $request)
    {
        try {
            $this->validateExportRequest($request);

            $from = $request->from;
            $to = $request->to;
            $kendaraanIds = $this->filteredKendaraanQuery($request)->pluck('id');

            $pos = PurchaseOrder::with([
                'cv',
                'kendaraans' => function ($q) use ($kendaraanIds) {
                    $q->whereIn('id', $kendaraanIds);
                },
                'kendaraans.supplier',
                'kendaraans.oaPayments' => function ($q) use ($request) {
                    $q
                        ->where('jumlah_bayar', '>', 0)
                        ->when($request->from, fn($q) => $q->whereDate('tanggal_bayar', '>=', $request->from))
                        ->when($request->to, fn($q) => $q->whereDate('tanggal_bayar', '<=', $request->to))
                        ->when($request->tipe_pembayaran, fn($q) => $q->where('tipe_pembayaran', $request->tipe_pembayaran))
                        ->when($request->supplier_ids, fn($q) => $q->whereIn('supplier_id', (array) $request->supplier_ids));
                },
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])
                ->whereHas('kendaraans', fn($q) => $q->whereIn('id', $kendaraanIds))
                ->orderBy('tanggal_po', 'asc')
                ->orderBy('no_po', 'asc')
                ->get();

            $pdf = Pdf::loadView('pdf.purchase-order-period-supplier', compact('pos', 'from', 'to'))
                ->setPaper('legal', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'Pembayaran-OA-Supplier-' . now()->format('Ymd-His') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export PDF pembayaran supplier: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        $this->validateExportRequest($request);
        $kendaraans = $this->filteredKendaraanQuery($request)
            ->with(['po.cv', 'supplier', 'penerimas.pakans.kodePakan', 'penerimas.tujuan', 'oaPayments'])
            ->get();

        return Excel::download(
            new PembayaranSupplierExport($kendaraans, $request->from, $request->to),
            'Pembayaran-Supplier-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function validateExportRequest(Request $request): void
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'integer|exists:suppliers,id',
            'status_pembayaran' => 'nullable|in:lunas,belum_lunas',
            'tipe_pembayaran' => 'nullable|in:oa,dp_supplier',
        ]);
    }

    private function filteredKendaraanQuery(Request $request)
    {
        $tujuanIds = $this->getUserTujuan()->pluck('id');
        $activeCvId = session('active_cv');
        $supplierIds = array_filter((array) $request->input('supplier_ids', []));
        $status = $request->status_pembayaran;

        $tujuanFilter = function ($q, $ids) {
            $ids = collect($ids)->filter()->values();
            $q->where(function ($q) use ($ids) {
                $q->whereIn('tujuan_id', $ids)
                    ->orWhereHas('penerimas', fn($q) => $q->whereIn('tujuan_id', $ids))
                    ->orWhereHas('penerimas.penerima', fn($q) => $q->whereIn('tujuan_id', $ids));
            });
        };

        $paymentFilter = function ($q) use ($request) {
            $q->where('jumlah_bayar', '>', 0)
                ->when($request->tipe_pembayaran, fn($q) => $q->where('tipe_pembayaran', $request->tipe_pembayaran));
        };

        return PoKendaraan::query()
            ->where('status', '!=', 'batal')
            ->when($activeCvId, fn($q) => $q->whereHas('po', fn($q) => $q->where('cv_id', $activeCvId)))
            ->when($supplierIds, fn($q) => $q->whereIn('supplier_id', $supplierIds))
            ->when($request->tujuan_id, fn($q) => $tujuanFilter($q, [$request->tujuan_id]))
            ->where(fn($q) => $tujuanFilter($q, $tujuanIds))
            ->when($status === 'belum_lunas', function ($q) use ($request) {
                $q->whereDoesntHave('oaPayments', fn($q) => $q->where('status', 'lunas'))
                    ->whereHas('po', fn($q) => $q->whereDate('tanggal_po', '>=', $request->from)->whereDate('tanggal_po', '<=', $request->to));
            }, function ($q) use ($request, $paymentFilter) {
                $q->whereHas('oaPayments', function ($q) use ($request, $paymentFilter) {
                    $paymentFilter($q);
                    $q->where('status', 'lunas');
                    $q->whereDate('tanggal_bayar', '>=', $request->from)->whereDate('tanggal_bayar', '<=', $request->to);
                });
            });
    }

    private function paidPaymentQuery(Request $request)
    {
        $tujuans = $this->getUserTujuan();
        $activeCvId = session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;
        $tipePembayaran = $request->tipe_pembayaran;

        return OaPayment::query()
            ->whereNotNull('po_kendaraan_id')
            ->where('jumlah_bayar', '>', 0)
            ->when($from, fn($q) => $q->whereDate('tanggal_bayar', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('tanggal_bayar', '<=', $to))
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->when($tipePembayaran, fn($q) => $q->where('tipe_pembayaran', $tipePembayaran))
            ->when($activeCvId, fn($q) => $q->whereHas('kendaraan.po', fn($po) => $po->where('cv_id', $activeCvId)))
            ->whereHas('kendaraan', fn($q) => $q->where('status', '!=', 'batal'))
            ->whereHas('kendaraan.penerimas.penerima', function ($q) use ($tujuans, $tujuanId) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'))
                    ->when($tujuanId, fn($q) => $q->where('tujuan_id', $tujuanId));
            });
    }

    public function index(Request $request)
    {
        $tujuans = $this->getUserTujuan();
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
                'kendaraan.penerimas.penerima',
            ])
                ->whereHas('kendaraan.penerimas.penerima', function ($q) use ($tujuans) {
                    $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                })
                ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($tipePembayaran, fn($q) => $q->where('tipe_pembayaran', $tipePembayaran))
                ->when($from, fn($q) => $q->whereDate('tanggal_bayar', '>=', $from))
                ->when($to, fn($q) => $q->whereDate('tanggal_bayar', '<=', $to))
                ->when($activeCvId, function ($q) use ($activeCvId) {
                    // Filter by CV untuk kedua tipe pembayaran
                    $q->where(function ($q) use ($activeCvId) {
                        $q->whereHas('penerima.kendaraan.po', fn($q) => $q->where('cv_id', $activeCvId))
                            ->orWhereHas('kendaraan.po', fn($q) => $q->where('cv_id', $activeCvId));
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
                ->addColumn('supplier_nama', fn($q) => $q->supplier?->nama ?? '-')
                ->addColumn('sisa', fn($q) => max(0, $q->sisa_tagihan))
                ->editColumn('tanggal_bayar', fn($q) => $q->tanggal_bayar ? $q->tanggal_bayar->format('d/m/Y') : '-')
                ->editColumn('metode_bayar', fn($q) => $q->metode_bayar ? ucfirst($q->metode_bayar) : '-')
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

                    return "<a href='" . asset('storage/' . $q->bukti_bayar) . "' target='_blank'
                                class='btn btn-xs btn-outline-secondary'>
                                <i class='fa fa-file'></i> Lihat
                            </a>";
                })
                ->addColumn('aksi', function ($q) {
                    return '<form action="' . route('keuangan.pembayaran.destroy', $q->id) . '" method="POST" onsubmit="return confirm(\'Yakin ingin membatalkan pembayaran ini? Data akan dihapus secara permanen.\')">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="btn btn-xs btn-outline-danger">Batalkan</button>
                            </form>';
                })
                ->addIndexColumn()
                ->rawColumns(['tipe', 'status_badge', 'bukti', 'tujuan', 'no_po', 'cv_name', 'no_polisi', 'aksi'])
                ->make(true);
        }

        $suppliers = Supplier::orderBy('nama')->get();

        // Summary card
        $activeCvId = session('active_cv');
        $base = OaPayment::when($activeCvId, function ($q) use ($activeCvId) {
            $q->where(function ($q) use ($activeCvId) {
                $q->whereHas('penerima.kendaraan.po', fn($q) => $q->where('cv_id', $activeCvId))
                    ->orWhereHas('kendaraan.po', fn($q) => $q->where('cv_id', $activeCvId));
            });
        });

        // === Ambil semua PoKendaraan yang relevan ===
        $poKendaraan = PoKendaraan::with([
            'penerimas:id,po_kendaraan_id',
            'penerimas.pakans:id,po_penerima_id,jumlah_kg,ongkos_oa',
            'penerimas.penerima'
        ])
            ->select('id', 'status', 'po_id')
            ->where('status', '!=', 'batal')
            ->whereHas('po', function ($q) use ($activeCvId) {
                if ($activeCvId) {
                    $q->where('cv_id', $activeCvId);
                }
            })
            ->whereHas('penerimas.penerima', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->get();

        $kendaraanIds = $poKendaraan->pluck('id');

        // === Hitung total tagihan dari accessor (tanpa loop manual) ===
        $total = $poKendaraan->sum(fn($po) => $po->total_tagihan_supplier);
        // dd($total);

        // === Count belum bayar — pakai IDs yang sudah ada ===
        $belumBayar = PoKendaraan::whereIn('id', $kendaraanIds)
            ->whereDoesntHave('oaPayment')
            ->count();

        // === OaPayment rows — query bersih tanpa closure & unique ===
        $oaPaymentRows = OaPayment::with('kendaraan.penerimas.penerima')
            ->whereIn('po_kendaraan_id', $kendaraanIds)
            ->whereIn('tipe_pembayaran', ['oa', 'dp_supplier'])
            ->whereHas('kendaraan.penerimas.penerima', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->select('id', 'jumlah_bayar', 'status')
            ->get();

        $oaTotalBayar = (float) $oaPaymentRows->sum('jumlah_bayar');
        $oaTotalSisa  = max(0, $total - $oaTotalBayar);

        $summary = [
            'total_tagihan' => $total,
            'total_bayar'   => $oaTotalBayar,
            'total_sisa'    => $oaTotalSisa,
            'count_pending' => $belumBayar,
            'count_partial' => $oaPaymentRows->where('status', 'partial')->count(),
            'count_lunas'   => $oaPaymentRows->where('status', 'lunas')->count(),
            'total_dp'      => (clone $base)->where('tipe_pembayaran', 'dp_supplier')->sum('jumlah_bayar'),
        ];
        return view('pages.keuangan.pembayaran.index', compact('suppliers', 'summary'));
    }

    public function destroy($id)
    {
        $oaPayment = OaPayment::findOrFail($id);
        $oaPayment->delete();

        return redirect()->back()->with('success', 'Pembayaran berhasil dibatalkan dan data telah dihapus.');
    }
}
