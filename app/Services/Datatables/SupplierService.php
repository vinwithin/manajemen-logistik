<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class SupplierService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('action', function ($q) {
                return view('pages.supplier._action', compact('q'));
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
