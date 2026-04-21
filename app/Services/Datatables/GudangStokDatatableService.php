<?php

namespace App\Services\Datatables;

use App\Models\GudangStok;
use Yajra\DataTables\DataTables;

class GudangStokDatatableService
{
    public function getData($request)
    {
        $query = GudangStok::with(['tujuan', 'kodePakan']);

        if ($request->filled('tujuan_id')) {
            $query->where('tujuan_id', $request->tujuan_id);
        }

        if ($request->filled('kode_pakan_id')) {
            $query->where('kode_pakan_id', $request->kode_pakan_id);
        }

        return DataTables::of($query)
            ->addColumn('nama_gudang', fn($q) => $q->tujuan?->nama ?? '-')
            ->addColumn('kode_pakan', fn($q) => $q->kodePakan?->kode ?? '-')
            ->addColumn('nama_pakan', fn($q) => $q->kodePakan?->nama ?? '-')
            ->addColumn('stok_kg_fmt', fn($q) => number_format($q->stok_kg, 2, ',', '.') . ' kg')
            ->addColumn('stok_karung_fmt', fn($q) => number_format($q->stok_karung, 0, ',', '.') . ' karung')
            ->addColumn('action', function ($q) {
                $mutasiUrl = route('gudang.mutasi.index', ['tujuan_id' => $q->tujuan_id]);
                return "<a href=\"{$mutasiUrl}\" class=\"btn btn-xs btn-outline-info py-0 px-1 small\">
                    <i class=\"fa fa-history\"></i> Lihat Mutasi
                </a>";
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
