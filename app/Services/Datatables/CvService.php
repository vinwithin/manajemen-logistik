<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class CvService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('is_aktif', function ($q) {
                if ($q->is_aktif) {
                    return "<div class='badge bg-success'>Aktif</div>";
                }

                return "<div class='badge bg-secondary'>Tidak Aktif</div>";
            })
            ->addColumn('action', function ($q) {
                return view('pages.cv._action', compact('q'));
            })
            ->addIndexColumn()
            ->rawColumns(['is_aktif', 'action'])
            ->make(true);
    }
}
