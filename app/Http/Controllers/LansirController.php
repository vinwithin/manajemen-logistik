<?php

namespace App\Http\Controllers;

use App\Models\PoPenerima;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class LansirController extends Controller
{
    use WithUserTujuan;
    // ── Daftar penerima yang memiliki lansir ──────────────────
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $activeCvId = session('active_cv');
            $tujuans = $this->getUserTujuan();

            $query = PoPenerima::with([
                'kendaraan.po.cv',
                'kendaraan.po',
                'kendaraan.supplier',
                'tujuan',
                'lansirs',
                'pakans.kodePakan',
                'penerima'
            ])

                ->whereHas('lansirs') // Hanya penerima yang memiliki lansir
                ->whereHas('kendaraan.po', function ($q) use ($activeCvId) {
                    if ($activeCvId) {
                        $q->where('cv_id', $activeCvId);
                    }
                })
                ->whereHas('penerima', function ($q) use ($tujuans) {
                    $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                })
                ->join('po_kendaraan', 'po_kendaraan.id', '=', 'po_penerima.po_kendaraan_id')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
                ->orderBy('purchase_orders.created_at', 'desc')
                ->select('po_penerima.*');

            if ($request->from && $request->to) {
                $query->whereHas('lansirs', function ($q) use ($request) {
                    $q->whereDate('tanggal_lansir', '>=', $request->from)
                        ->whereDate('tanggal_lansir', '<=', $request->to);
                });
            }

            return DataTables::of($query)
                ->addColumn('no_po', fn($q) => $q->kendaraan->po->no_po ?? '-')
                ->addColumn('tanggal_po', fn($q) => $q->lansirs->first()->tanggal_lansir?->format('d/m/Y') ?? '-')
                ->addColumn('cv_name', fn($q) => $q->kendaraan->po->cv?->nama_cv ?? '-')
                ->addColumn('no_polisi', fn($q) => $q->kendaraan->no_polisi ?? '-')
                ->addColumn('tujuan_nama', fn($q) => $q->tujuan?->nama ?? '-')
                ->addColumn('berat', fn($q) => $q->total_kg)
                ->addColumn('jumlah_trip', fn($q) => $q->lansirs->count() . ' trip')
                ->addColumn('action', function ($q) {
                    $url = route('po-penerima.lansir-page', encrypt($q->id));

                    return "<a href='{$url}' class='btn btn-xs btn-primary text-white'>
                                <i class='fa fa-history'></i> Lihat 
                            </a>";
                })
                ->addIndexColumn()
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.lansir.index');
    }
}
