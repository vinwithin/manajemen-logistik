<?php

namespace App\Services\Datatables;

use App\Models\Tujuan;
use Yajra\DataTables\DataTables;

class TujuanService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('type_label', function ($q) {
                $labels = [
                    'direct'    => '<span class="badge bg-primary">Direct</span>',
                    'gudang'    => '<span class="badge bg-info">Gudang</span>',
                    'co_farm'   => '<span class="badge bg-warning text-dark">Co Farm</span>',
                    'rent_farm' => '<span class="badge bg-secondary">Rent Farm</span>',
                ];
                return $labels[$q->type] ?? '<span class="badge bg-light text-dark">' . $q->type . '</span>';
            })
            ->addColumn('is_aktif', function ($q) {
                return $q->is_aktif
                    ? "<span class='badge bg-success'>Aktif</span>"
                    : "<span class='badge bg-secondary'>Tidak Aktif</span>";
            })
            ->addColumn('action', function ($q) {
                return view('pages.tujuan._action', compact('q'));
            })
            ->addIndexColumn()
            ->rawColumns(['type_label', 'is_aktif', 'action'])
            ->make(true);
    }
}
