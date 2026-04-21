<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class KodePakanService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('action', function ($q) {
                return view('pages.kode-pakan._action', compact('q'));
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
