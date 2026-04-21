<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class RoleService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('permissions_count', function ($q) {
                return $q->permissions->count();
            })
            ->addColumn('action', function ($q) {
                return view('pages.role._action', compact('q'));
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
