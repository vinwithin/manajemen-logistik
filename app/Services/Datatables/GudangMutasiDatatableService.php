<?php

namespace App\Services\Datatables;

use App\Models\GudangMutasiStok;
use Yajra\DataTables\DataTables;

class GudangMutasiDatatableService
{
    public function getData($request)
    {
        $query = GudangMutasiStok::with(['tujuan', 'kodePakan', 'poPenerima.kendaraan.po']);

        if ($request->filled('tujuan_id')) {
            $query->where('tujuan_id', $request->tujuan_id);
        }

        if ($request->filled('kode_pakan_id')) {
            $query->where('kode_pakan_id', $request->kode_pakan_id);
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('created_at', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('created_at', '<=', $request->sampai_tanggal);
        }

        return DataTables::of($query)
            ->addColumn('tanggal', fn ($q) => $q->created_at?->format('d/m/Y H:i') ?? '-')
            ->addColumn('nama_gudang', fn ($q) => $q->tujuan?->nama ?? '-')
            ->addColumn('kode_pakan', fn ($q) => $q->kodePakan?->kode ?? '-')
            ->addColumn('tipe_badge', function ($q) {
                return $q->tipe === 'masuk'
                    ? "<span class='badge bg-success'>Masuk</span>"
                    : "<span class='badge bg-danger'>Keluar</span>";
            })
            ->addColumn('jumlah_kg_fmt', fn ($q) => number_format($q->jumlah_kg, 0, ',', '.').' kg')
            ->addColumn('jumlah_karung_fmt', fn ($q) => $q->jumlah_karung.' karung')
            ->addColumn('saldo_kg_after_fmt', fn ($q) => number_format($q->saldo_kg_after, 0, ',', '.').' kg')
            ->addColumn('referensi', function ($q) {
                if ($q->poPenerima) {
                    $po = $q->poPenerima->kendaraan->po ?? null;
                    $noPo = $po ? $po->no_po : '-';

                    return "<div class='small'>
                        <strong>{$q->poPenerima->nama_penerima}</strong><br>
                        <span class='text-muted'>PO: {$noPo}</span>
                    </div>";
                }

                return $q->referensi_tipe.' #'.$q->referensi_id;
            })
            ->addIndexColumn()
            ->rawColumns(['tipe_badge', 'referensi'])
            ->make(true);
    }
}
