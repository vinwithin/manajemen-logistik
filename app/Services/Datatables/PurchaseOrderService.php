<?php

namespace App\Services\Datatables;

use Yajra\DataTables\DataTables;

class PurchaseOrderService
{
    public function getData($query)
    {
        return DataTables::of($query)
            ->addColumn('cv_name', fn($q) => $q->cv?->nama_cv ?? '-')
            ->addColumn('jumlah_mobil', function ($q) {
                $nopols = $q->kendaraans->pluck('no_polisi')->map(fn($n) => trim($n))->filter()->unique();

                return $nopols->isNotEmpty() ? $nopols->implode(', ') : '-';
            })
            ->filterColumn('jumlah_mobil', function ($query, $keyword) {
                $query->whereHas('kendaraans', function ($q) use ($keyword) {
                    $q->where('no_polisi', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('tanggal_po', fn($q) => $q->tanggal_po->format('d/m/Y'))
            ->addColumn('status_badge', function ($q) {
                return $q->status === 'locked'
                    ? "<span class='badge bg-success'>Terkunci</span>"
                    : "<span class='badge bg-warning text-dark'>Draft</span>";
            })
            ->addColumn('action', function ($q) {
                return view('pages.purchase-order._action', compact('q'));
            })
            ->addIndexColumn()
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }
}
