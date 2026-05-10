<?php

namespace App\Services\Datatables;

use Yajra\DataTables\Facades\DataTables;

class PenerimaService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('tujuan_nama', function ($row) {
                return $row->tujuan->nama ?? '-';
            })
            ->addColumn('ongkos_formatted', function ($row) {
                return 'Rp ' . number_format($row->ongkos_angkut, 0, ',', '.');
            })
            ->addColumn('bongkar_formatted', function ($row) {
                return $row->ongkos_bongkar > 0
                    ? 'Rp ' . number_format($row->ongkos_bongkar, 0, ',', '.')
                    : '-';
            })
            ->addColumn('status', function ($row) {
                return $row->is_aktif 
                    ? '<span class="badge bg-success">Aktif</span>' 
                    : '<span class="badge bg-secondary">Nonaktif</span>';
            })
            ->addColumn('action', function ($row) {
                return view('pages.penerima._action', compact('row'))->render();
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }
}
