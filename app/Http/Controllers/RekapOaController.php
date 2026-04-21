<?php

namespace App\Http\Controllers;

use App\Models\OaPayment;
use App\Models\PoPenerima;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RekapOaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $activeCvId = session('active_cv');
            $supplierId = $request->supplier_id;
            $status     = $request->status;
            $from       = $request->from;
            $to         = $request->to;

            $query = PoPenerima::with(['kendaraan.po.cv', 'kendaraan.supplier', 'tujuan', 'pakans', 'oaPayment'])
                ->whereIn('status', ['selesai', 'batal'])
                ->whereHas('kendaraan.po', function ($q) use ($activeCvId, $from, $to) {
                    if ($activeCvId) $q->where('cv_id', $activeCvId);
                    if ($from) $q->whereDate('tanggal_po', '>=', $from);
                    if ($to)   $q->whereDate('tanggal_po', '<=', $to);
                })
                ->when($supplierId, fn($q) => $q->whereHas('kendaraan', fn($q2) => $q2->where('supplier_id', $supplierId)))
                ->when($status, function ($q) use ($status) {
                    if ($status === 'pending') {
                        $q->whereDoesntHave('oaPayment')
                          ->orWhereHas('oaPayment', fn($q2) => $q2->where('status', 'pending'));
                    } else {
                        $q->whereHas('oaPayment', fn($q2) => $q2->where('status', $status));
                    }
                })
                ->select('po_penerima.*');

            return DataTables::of($query)
                ->addColumn('no_po',        fn($q) => $q->kendaraan->po->no_po)
                ->addColumn('tanggal_po',   fn($q) => $q->kendaraan->po->tanggal_po->format('d/m/Y'))
                ->addColumn('cv_name',      fn($q) => $q->kendaraan->po->cv?->nama_cv ?? '-')
                ->addColumn('no_polisi',    fn($q) => $q->kendaraan->no_polisi)
                ->addColumn('supplier_nama',fn($q) => $q->kendaraan->supplier?->nama ?? '-')
                ->addColumn('tujuan_nama',  fn($q) => $q->tujuan?->nama ?? '-')
                ->addColumn('total_kg',     fn($q) => $q->total_kg)
                ->addColumn('total_oa',     fn($q) => $q->total_oa)
                ->addColumn('status_bayar', function ($q) {
                    $s = $q->oaPayment?->status ?? 'pending';
                    $map = ['pending' => ['secondary','Belum Bayar'], 'partial' => ['warning','Bayar Sebagian'], 'lunas' => ['success','Lunas']];
                    [$color, $label] = $map[$s];
                    return "<span class='badge bg-{$color}'>{$label}</span>";
                })
                ->addColumn('sisa', fn($q) => max(0, $q->total_oa - ($q->oaPayment?->jumlah_bayar ?? 0)))
                ->addColumn('action', function ($q) {
                    $url = route('keuangan.oa.bayar', encrypt($q->id));
                    return "<a href='{$url}' class='btn btn-xs btn-primary'><i class='fa fa-money'></i> Bayar</a>";
                })
                ->addIndexColumn()
                ->rawColumns(['status_bayar', 'action'])
                ->make(true);
        }

        $suppliers = Supplier::orderBy('nama')->get();
        return view('pages.keuangan.oa.index', compact('suppliers'));
    }

    public function bayar(string $id)
    {
        $penerima = PoPenerima::with(['kendaraan.po.cv', 'kendaraan.supplier', 'tujuan', 'pakans.kodePakan', 'oaPayment'])
            ->findOrFail(decrypt($id));

        $tagihan = $penerima->total_oa;
        return view('pages.keuangan.oa.bayar', compact('penerima', 'tagihan'));
    }

    public function storeBayar(Request $request, string $id)
    {
        $request->validate([
            'jumlah_bayar'  => 'required|numeric|min:1',
            'tanggal_bayar' => 'required|date',
            'metode_bayar'  => 'required|in:transfer,tunai,cek',
            'bukti_bayar'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'keterangan'    => 'nullable|string|max:255',
        ]);

        $penerima = PoPenerima::with('kendaraan')->findOrFail($id);
        $tagihan  = $penerima->total_oa;

        $path = null;
        if ($request->hasFile('bukti_bayar')) {
            $path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');
        }

        $existing   = OaPayment::where('po_penerima_id', $penerima->id)->first();
        $totalBayar = ($existing?->jumlah_bayar ?? 0) + $request->jumlah_bayar;
        $status     = $totalBayar >= $tagihan ? 'lunas' : 'partial';

        OaPayment::updateOrCreate(
            ['po_penerima_id' => $penerima->id],
            [
                'supplier_id'    => $penerima->kendaraan->supplier_id,
                'jumlah_tagihan' => $tagihan,
                'jumlah_bayar'   => $totalBayar,
                'tanggal_bayar'  => $request->tanggal_bayar,
                'metode_bayar'   => $request->metode_bayar,
                'bukti_bayar'    => $path ?? $existing?->bukti_bayar,
                'keterangan'     => $request->keterangan,
                'status'         => $status,
            ]
        );

        return redirect()->route('keuangan.oa.index')->with('success', 'Pembayaran berhasil dicatat.');
    }
}
