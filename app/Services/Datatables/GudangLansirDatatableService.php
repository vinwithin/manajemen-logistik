<?php

namespace App\Services\Datatables;

use App\Models\GudangLansirHeader;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class GudangLansirDatatableService
{
    public function getData($request)
    {
        $query = GudangLansirHeader::select('gudang_lansir_header.*')
            ->with(['gudang', 'kendaraans.penerimas.pakans.kodePakan', 'kendaraans.penerimas.tujuan'])
            ->latest();

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_lansir', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_lansir', '<=', $request->sampai_tanggal);
        }

        return DataTables::of($query)
            ->addColumn('no_lansir', fn($q) => $q->no_lansir)
            ->addColumn('tanggal', fn($q) => $q->tanggal_lansir?->format('d/m/Y') ?? '-')
            ->addColumn('nama_gudang', fn($q) => $q->gudang?->nama ?? '-')
            ->addColumn('jumlah_kendaraan', fn($q) => $q->jumlah_kendaraan . ' mobil')
            ->addColumn('jumlah_penerima', fn($q) => $q->jumlah_penerima . ' penerima')
            ->addColumn('total_kg_fmt', fn($q) => number_format($q->total_kg, 0, ',', '.') . ' kg')
            ->addColumn('total_karung_fmt', fn($q) => number_format($q->total_karung, 0, ',', '.') . ' karung')
            ->addColumn('pakan_list', function ($q) {
                $pakans = $q->kendaraans
                    ->flatMap->penerimas
                    ->flatMap->pakans
                    ->pluck('kodePakan.kode')
                    ->unique()
                    ->filter();

                return $pakans->count() > 0 ? $pakans->implode(', ') : '-';
            })
            ->addColumn('status_pengiriman', function ($q) {
                $allPenerimas = $q->kendaraans->flatMap->penerimas;
                $total = $allPenerimas->count();
                $selesai = $allPenerimas->where('status', 'selesai')->count();
                $tiba = $allPenerimas->where('status', 'tiba')->count();
                $perjalanan = $allPenerimas->where('status', 'dalam_perjalanan')->count();

                if ($selesai === $total) {
                    return '<span class="badge bg-success">Semua Selesai</span>';
                } elseif ($perjalanan === $total) {
                    return '<span class="badge bg-warning">Dalam Perjalanan</span>';
                } else {
                    return "<span class=\"badge bg-info\">$selesai/$total Selesai</span>";
                }
            })
            ->addColumn('action', function ($q) {
                $showUrl = route('gudang.lansir.show', encrypt($q->id));
                $editUrl = route('gudang.lansir.edit', encrypt($q->id));

                return "<div class=\"d-flex gap-1\">
                    <a href=\"{$showUrl}\" class=\"btn btn-xs btn-info text-white\">
                        <i class=\"fa fa-eye\"></i> Detail
                    </a>
                    <a href=\"{$editUrl}\" class=\"btn btn-xs btn-warning text-white\">
                        <i class=\"fa fa-edit\"></i> Edit
                    </a>
                </div>";
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status_pengiriman'])
            ->make(true);
    }
}
