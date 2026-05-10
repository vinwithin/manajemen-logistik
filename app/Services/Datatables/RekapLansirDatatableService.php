<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class RekapLansirDatatableService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('tanggal_po', fn($q) => $q->tanggal_po->format('d/m/Y'))
            ->addColumn('cv_name', fn($q) => $q->cv?->nama_cv ?? '-')
            ->addColumn('jumlah_kendaraan', fn($q) => ($q->kendaraans_count ?? 0) . ' kendaraan')
            ->addColumn('action', function ($q) {
                $url = route('rekap-lansir.show', encrypt($q->id));
                return "<a href='{$url}' class='btn btn-sm btn-primary'>
                            <i class='fa fa-eye'></i> Lihat Rekap
                        </a>";
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
