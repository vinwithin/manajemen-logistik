<?php

namespace App\Services\Datatables;

use Yajra\DataTables\Facades\DataTables;

class MobilService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('status', function ($row) {
                return $row->is_aktif
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>';
            })
            ->addColumn('action', function ($row) {
                return view('pages.mobil._action', compact('row'))->render();
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }
}
