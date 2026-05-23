<?php

namespace App\Http\Controllers;

use App\Models\OaPayment;
use App\Models\PoKendaraan;
use App\Models\Supplier;
use App\Models\PoPenerima;
use App\Models\PurchaseOrder;
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
        ])
            ->select('id', 'status', 'po_id')
            ->where('status', '!=', 'batal')
            ->whereHas('po', function ($q) use ($activeCvId) {
                if ($activeCvId) {
                    $q->where('cv_id', $activeCvId);
                }
            })
            ->get();

        $kendaraanIds = $poKendaraan->pluck('id');

        // === Hitung total tagihan dari accessor (tanpa loop manual) ===
        $total = $poKendaraan->sum(fn($po) => $po->total_tagihan_supplier);

        // === Count belum bayar — pakai IDs yang sudah ada ===
        $belumBayar = PoKendaraan::whereIn('id', $kendaraanIds)
            ->whereDoesntHave('oaPayment')
            ->count();

        // === OaPayment rows — query bersih tanpa closure & unique ===
        $oaPaymentRows = OaPayment::whereIn('po_kendaraan_id', $kendaraanIds)
            ->whereIn('tipe_pembayaran', ['oa', 'dp_supplier'])
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
