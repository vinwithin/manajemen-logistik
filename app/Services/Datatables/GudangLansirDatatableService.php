<?php

namespace App\Services\Datatables;

use App\Models\GudangLansirKendaraan;
use Yajra\DataTables\DataTables;

class GudangLansirDatatableService
{
    public function getData($request)
    {
        $query = GudangLansirKendaraan::with(['gudang', 'penerimas.pakans.kodePakan', 'penerimas.tujuan']);

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
            ->addColumn('tanggal', fn($q) => $q->tanggal_lansir?->format('d/m/Y') ?? '-')
            ->addColumn('nama_gudang', fn($q) => $q->gudang?->nama ?? '-')
            ->addColumn('no_polisi', fn($q) => $q->no_polisi)
            ->addColumn('nama_sopir', fn($q) => $q->nama_sopir ?? '-')
            ->addColumn('jumlah_penerima', fn($q) => $q->penerimas->count() . ' penerima')
            ->addColumn('total_kg_fmt', fn($q) => number_format($q->total_kg, 0, ',', '.') . ' kg')
            ->addColumn('total_karung_fmt', fn($q) => number_format($q->total_karung, 0, ',', '.') . ' karung')
            ->addColumn('pakan_list', function ($q) {
                $pakans = $q->penerimas->flatMap->pakans->pluck('kodePakan.kode')->unique()->filter();
                return $pakans->count() > 0 ? $pakans->implode(', ') : '-';
            })
            ->addColumn('action', function ($q) {
                $showUrl = route('gudang.lansir.show', encrypt($q->id));
                return "<a href=\"{$showUrl}\" class=\"btn btn-xs btn-info text-white\">
                    <i class=\"fa fa-eye\"></i> Detail
                </a>";
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
