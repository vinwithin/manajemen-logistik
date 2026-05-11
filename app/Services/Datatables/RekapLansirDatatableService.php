<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class RekapLansirDatatableService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('no_po', fn($q) => $q->penerima?->kendaraan?->po?->no_po ?? '-')
            ->addColumn('tanggal_lansir', fn($q) => $q->tanggal_lansir?->format('d/m/Y') ?? '-')
            ->addColumn('nama_penerima', fn($q) => $q->penerima?->nama_penerima ?? '-')
            ->addColumn('cv_name', fn($q) => $q->penerima?->kendaraan?->po?->cv?->nama_cv ?? '-')
            ->addColumn('jumlah_kendaraan', fn($q) => ($q->mobils_count ?? 0) . ' kendaraan')
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

    public function getDataFromCollection($collection)
    {
        return DataTables::of($collection)
            ->addColumn('tipe', fn($q) => '<span class="badge bg-' . ($q['tipe'] == 'PO Lansir' ? 'primary' : 'success') . '">' . $q['tipe'] . '</span>')
            ->addColumn('no_referensi', fn($q) => $q['no_referensi'])
            ->addColumn('tanggal_lansir', fn($q) => $q['tanggal_lansir']?->format('d/m/Y') ?? '-')
            ->addColumn('nama_tujuan', fn($q) => $q['nama_tujuan'])
            ->addColumn('cv_name', fn($q) => $q['cv_name'])
            ->addColumn('jumlah_kendaraan', fn($q) => $q['jumlah_kendaraan'] . ' kendaraan')
            ->addColumn('action', function ($q) {
                $url = route('rekap-lansir.show', $q['id']);
                return "<a href='{$url}' class='btn btn-sm btn-primary'>
                            <i class='fa fa-eye'></i> Lihat Rekap
                        </a>";
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'tipe'])
            ->make(true);
    }
}
