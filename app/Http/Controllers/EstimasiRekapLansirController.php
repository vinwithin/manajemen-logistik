<?php

namespace App\Http\Controllers;

use App\Exports\EstimasiRekapLansirExport;
use App\Models\PoPenerima;
use App\Traits\WithUserTujuan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\DataTables;

class EstimasiRekapLansirController extends Controller
{
    use WithUserTujuan;

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $baseQuery = $this->query($filters);

        if ($request->ajax()) {
            return $this->datatable($baseQuery);
        }

        return view('pages.keuangan.estimasi-rekap-lansir.index', compact('filters'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ], [
            'from.required' => 'Dari tanggal wajib diisi untuk export.',
            'to.required' => 'Sampai tanggal wajib diisi untuk export.',
            'to.after_or_equal' => 'Sampai tanggal tidak boleh sebelum dari tanggal.',
        ]);

        $filters = $this->filters($request);
        $penerimas = $this->query($filters)->get();
        $filename = "estimasi-lansir-bongkar-{$validated['from']}-{$validated['to']}.xlsx";

        return Excel::download(
            new EstimasiRekapLansirExport(
                $penerimas,
                date('d/m/Y', strtotime($validated['from'])),
                date('d/m/Y', strtotime($validated['to'])),
            ),
            $filename
        );
    }

    private function datatable(Builder $query)
    {
        return DataTables::of($query)
            ->addColumn('estimasi_tiba_display', function (PoPenerima $penerima) {
                $date = $penerima->estimasi_tiba?->format('d/m/Y') ?? '-';
                $isLate = $penerima->estimasi_tiba
                    && $penerima->estimasi_tiba->lt(now()->startOfDay())
                    && $penerima->lansirs->isEmpty();

                return $isLate
                    ? $date . '<br><span class="badge bg-danger">Terlambat</span>'
                    : $date;
            })
            ->addColumn('no_po', function (PoPenerima $penerima) {
                $po = $penerima->kendaraan?->po;
                if (! $po) {
                    return '-';
                }

                $url = route('purchase-order.show', encrypt($po->id));
                return "<a href=\"{$url}\">{$po->no_po}</a>";
            })
            ->addColumn('kendaraan_display', function (PoPenerima $penerima) {
                $nopol = $penerima->kendaraan?->no_polisi ?? '-';
                $sopir = $penerima->kendaraan?->nama_sopir ?? '';

                return '<div class="fw-semibold">' . e($nopol) . '</div><small class="text-muted">' . e($sopir) . '</small>';
            })
            ->addColumn('tujuan_display', fn(PoPenerima $penerima) => $penerima->tujuan?->nama
                ?? $penerima->penerima?->tujuan?->nama
                ?? $penerima->kendaraan?->tujuan?->nama
                ?? '-')
            ->addColumn('pakan_display', function (PoPenerima $penerima) {
                return $penerima->pakans
                    ->map(fn($pakan) => '<span class="badge bg-light text-dark border">' . e($pakan->kodePakan?->kode ?? '-') . '</span>')
                    ->implode(' ');
            })
            ->addColumn('total_kg', fn(PoPenerima $penerima) => (float) $penerima->pakans->sum('jumlah_kg'))
            ->addColumn('total_karung', fn(PoPenerima $penerima) => (float) $penerima->pakans->sum('jumlah_karung'))
            ->addColumn('status_lansir', function (PoPenerima $penerima) {
                $badge = $penerima->lansirs->isEmpty()
                    ? '<span class="badge bg-warning text-dark">Belum Lansir</span>'
                    : '<span class="badge bg-info text-dark">Sudah Ada Lansir</span>';

                return $badge;
            })
            ->addColumn('action', function (PoPenerima $penerima) {
                $actions = [];

                if (auth()->user()?->can('lansir.create')) {
                    $url = route('po-penerima.lansir-page', $penerima->id);
                    $actions[] = "<a href=\"{$url}\" class=\"btn btn-sm btn-outline-primary\">Lansir</a>";
                }

                if (auth()->user()?->can('po.view') && $penerima->kendaraan?->po) {
                    $url = route('purchase-order.show', encrypt($penerima->kendaraan->po->id));
                    $actions[] = "<a href=\"{$url}\" class=\"btn btn-sm btn-outline-secondary\">Detail</a>";
                }

                return '<div class="d-flex gap-1">' . implode('', $actions) . '</div>';
            })
            ->editColumn('no_do', fn(PoPenerima $penerima) => $penerima->no_do ?? '-')
            ->editColumn('nama_penerima', fn(PoPenerima $penerima) => e($penerima->nama_penerima))
            ->filter(function ($query) {
                $search = request('search.value');
                if (! $search) {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('nama_penerima', 'like', "%{$search}%")
                        ->orWhere('no_do', 'like', "%{$search}%")
                        ->orWhereHas('kendaraan', fn($k) => $k->where('no_polisi', 'like', "%{$search}%"))
                        ->orWhereHas('kendaraan.po', fn($po) => $po->where('no_po', 'like', "%{$search}%"));
                });
            })
            ->addIndexColumn()
            ->rawColumns(['estimasi_tiba_display', 'no_po', 'kendaraan_display', 'pakan_display', 'status_lansir', 'action'])
            ->make(true);
    }

    private function filters(Request $request): array
    {
        $search = $request->input('search', '');
        if (is_array($search)) {
            $search = $search['value'] ?? '';
        }

        return [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'search' => trim((string) $search),
        ];
    }

    private function query(array $filters): Builder
    {
        $tujuanIds = $this->getUserTujuan()->pluck('id');

        $query = PoPenerima::with([
            'kendaraan.po.cv',
            'kendaraan.supplier',
            'kendaraan.tujuan',
            'tujuan',
            'penerima.tujuan',
            'pakans.kodePakan',
            'lansirs',
        ])
            ->whereNotIn('po_penerima.status', ['selesai', 'batal'])
            ->where(function ($q) use ($tujuanIds) {
                $q->whereIn('po_penerima.tujuan_id', $tujuanIds)
                    ->orWhereHas('penerima', fn($p) => $p->whereIn('tujuan_id', $tujuanIds))
                    ->orWhereNull('po_penerima.tujuan_id');
            });

        if (! empty($filters['from'])) {
            $query->whereHas('kendaraan.po', fn($po) => $po->whereDate('tanggal_po', '>=', $filters['from']));
        }

        if (! empty($filters['to'])) {
            $query->whereHas('kendaraan.po', fn($po) => $po->whereDate('tanggal_po', '<=', $filters['to']));
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_penerima.nama_penerima', 'like', "%{$search}%")
                    ->orWhere('po_penerima.no_do', 'like', "%{$search}%")
                    ->orWhereHas('kendaraan', fn($k) => $k->where('no_polisi', 'like', "%{$search}%"))
                    ->orWhereHas('kendaraan.po', fn($po) => $po->where('no_po', 'like', "%{$search}%"));
            });
        }

        return $query
            ->select('po_penerima.*')
            ->leftJoin('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->orderBy('purchase_orders.tanggal_po', 'desc');
    }
}
