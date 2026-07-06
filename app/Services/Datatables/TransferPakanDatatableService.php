<?php

namespace App\Services\Datatables;

use App\Models\TransferPakanHeader;
use Yajra\DataTables\DataTables;

class TransferPakanDatatableService
{
    public function getData($request)
    {
        $activeCvId = session('active_cv');
        $from = $request->input('from');
        $to = $request->input('to');

        $query = TransferPakanHeader::with(['cv', 'kendaraans.penerimas.tujuan'])
            ->when($activeCvId, fn ($q) => $q->where('cv_id', $activeCvId))
            ->when($from, fn ($q) => $q->whereDate('tanggal_transfer', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('tanggal_transfer', '<=', $to))
            ->orderBy('tanggal_transfer', 'desc');

        return DataTables::of($query)
            ->addColumn('no_transfer', fn ($q) => $q->no_transfer)
            ->addColumn('tanggal', fn ($q) => $q->tanggal_transfer->format('d/m/Y'))
            ->addColumn('cv_name', fn ($q) => $q->cv?->nama_cv ?? '-')
            ->addColumn('pengirim', fn ($q) => $q->nama_pengirim ?? '-')
            ->addColumn('penerima', function ($q) {
                $names = $q->kendaraans
                    ->flatMap(fn ($k) => $k->penerimas)
                    ->pluck('nama_penerima')
                    ->filter()
                    ->unique()
                    ->values();
                if ($names->isEmpty()) {
                    return '-';
                }

                return $names->map(fn ($n) => '<span class="badge bg-light text-dark border">'.e($n).'</span>')
                    ->implode(' ');
            })
            ->addColumn('tujuan_nama', function ($q) {
                $names = $q->kendaraans
                    ->flatMap(fn ($k) => $k->penerimas)
                    ->map(fn ($p) => $p->tujuan?->nama)
                    ->filter()
                    ->unique()
                    ->values();

                if ($names->isEmpty()) {
                    return '-';
                }

                return $names->map(fn ($n) => '<span class="badge bg-light text-dark border">'.e($n).'</span>')
                    ->implode(' ');
            })
            ->addColumn('status', function ($q) {
                $statusMap = [
                    'pending' => ['bg' => 'warning', 'text' => 'dark',  'label' => 'Pending'],
                    'tiba' => ['bg' => 'info',    'text' => 'white', 'label' => 'Tiba'],
                    'selesai' => ['bg' => 'success', 'text' => 'white', 'label' => 'Selesai'],
                ];
                $statuses = $q->kendaraans
                    ->flatMap(fn ($k) => $k->penerimas)
                    ->pluck('status')
                    ->filter()
                    ->unique()
                    ->values();
                if ($statuses->isEmpty()) {
                    return '-';
                }

                return $statuses->map(function ($s) use ($statusMap) {
                    $cfg = $statusMap[$s] ?? ['bg' => 'secondary', 'text' => 'white', 'label' => ucfirst($s)];

                    return '<span class="badge bg-'.$cfg['bg'].' text-'.$cfg['text'].'">'.$cfg['label'].'</span>';
                })->implode(' ');
            })
            ->addColumn('action', function ($q) {
                $showUrl = route('transfer-pakan.show', encrypt($q->id));
                $editUrl = route('transfer-pakan.edit', encrypt($q->id));
                $deleteUrl = route('transfer-pakan.destroy', encrypt($q->id));

                return "
                    <a href='{$showUrl}' class='btn btn-xs btn-info text-white'>
                        <i class='fa fa-eye'></i> Detail
                    </a>
                    <a href='{$editUrl}' class='btn btn-xs btn-warning text-white'>
                        <i class='fa fa-edit'></i> Edit
                    </a>
                    <form action='{$deleteUrl}' method='POST' style='display: inline;' onsubmit='return confirm(\"Apakah Anda yakin ingin menghapus transfer pakan ini?\")'>
                        " . csrf_field() . "
                        " . method_field('DELETE') . "
                        <button type='submit' class='btn btn-xs btn-danger text-white'>
                            <i class='fa fa-trash'></i> Hapus
                        </button>
                    </form>
                ";
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'penerima', 'tujuan_nama', 'status'])
            ->make(true);
    }
}
