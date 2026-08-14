<?php

namespace App\Http\Controllers;

use App\Exports\RekapPtSumExport;
use App\Models\OaPayment;
use App\Models\PoKendaraan;
use App\Models\Supplier;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class RekapPtSumController extends Controller
{
    use WithUserTujuan;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->query($request);

            return DataTables::of($query)
                ->addColumn('no_po', fn ($q) => $q->po?->no_po ?? '-')
                ->addColumn('tanggal_po', fn ($q) => $q->po?->tanggal_po?->format('d/m/Y') ?? '-')
                ->addColumn('cv_name', fn ($q) => $q->po?->cv?->nama_cv ?? '-')
                ->addColumn('no_polisi', fn ($q) => $q->no_polisi)
                ->addColumn('supplier_nama', fn ($q) => $q->supplier?->nama ?? '-')
                ->addColumn('penerima_list', fn ($q) => $q->penerimas->pluck('nama_penerima')->join(', '))
                ->addColumn('total_kg', fn ($q) => $q->total_kg)
                ->addColumn('total_pt_sum', fn ($q) => $q->total_pt_sum)
                ->addColumn('harga_rata_rata', fn ($q) => $q->total_kg > 0 ? $q->total_pt_sum / $q->total_kg : 0)
                ->addColumn('status_bayar', function ($q) {
                    $isPaid = $q->ptSumPaymentOnly?->status === 'lunas';
                    $color = $isPaid ? 'success' : 'warning';
                    $label = $isPaid ? 'Sudah Dibayar' : 'Belum Dibayar';

                    return "<span class='badge bg-{$color}'>{$label}</span>";
                })
                ->addColumn('action', function ($q) {
                    if ($q->ptSumPaymentOnly?->status === 'lunas') {
                        return "<button type='button' class='btn btn-xs btn-success' disabled><i class='fa fa-check'></i> Lunas</button>";
                    }

                    $url = route('keuangan.rekap-pt-sum.store-bayar', encrypt($q->id));
                    $csrf = csrf_field();
                    $amount = number_format($q->total_pt_sum, 0, ',', '.');

                    return "<form method='POST' action='{$url}' class='d-inline form-bayar-ptsum' data-confirm='Tandai tagihan PT Sum {$q->no_polisi} sebesar Rp {$amount} sebagai sudah dibayar?'>{$csrf}<button type='submit' class='btn btn-xs btn-primary'><i class='fa fa-money'></i> Tandai Bayar</button></form>";
                })
                ->addIndexColumn()
                ->rawColumns(['status_bayar', 'action'])
                ->make(true);
        }

        $suppliers = Supplier::orderBy('nama')->get();

        return view('pages.keuangan.rekap-pt-sum.index', compact('suppliers'));
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], [
            'from.required' => 'Dari tanggal wajib diisi untuk export Excel.',
            'to.required' => 'Sampai tanggal wajib diisi untuk export Excel.',
            'to.after_or_equal' => 'Sampai tanggal harus sama atau setelah dari tanggal.',
        ]);

        $kendaraans = $this->query($request)->get();
        $filename = 'Rekap-PT-Sum-' . $request->from . '-sd-' . $request->to . '.xlsx';

        return Excel::download(new RekapPtSumExport($kendaraans, $request->from, $request->to), $filename);
    }

    public function storeBayar(string $id)
    {
        $kendaraan = PoKendaraan::with(['supplier', 'penerimas.pakans'])->findOrFail(decrypt($id));
        $tagihan = $kendaraan->total_pt_sum;

        if ($tagihan <= 0) {
            return redirect()->route('keuangan.rekap-pt-sum.index')
                ->with('error', 'Tagihan PT Sum masih 0, tidak dapat ditandai lunas.');
        }

        OaPayment::updateOrCreate(
            [
                'po_kendaraan_id' => $kendaraan->id,
                'tipe_pembayaran' => 'pt_sum',
            ],
            [
                'po_penerima_id' => null,
                'supplier_id' => $kendaraan->supplier_id,
                'jumlah_tagihan' => $tagihan,
                'jumlah_bayar' => $tagihan,
                'tanggal_bayar' => now()->toDateString(),
                'metode_bayar' => null,
                'bukti_bayar' => null,
                'keterangan' => 'Ditandai lunas dari Rekap PT Sum',
                'status' => 'lunas',
            ]
        );

        return redirect()->route('keuangan.rekap-pt-sum.index')
            ->with('success', 'Pembayaran PT Sum berhasil ditandai lunas.');
    }

    private function query(Request $request)
    {
        $activeCvId = session('active_cv');
        $supplierId = $request->supplier_id;
        $status = $request->status;
        $from = $request->from;
        $to = $request->to;
        $tujuans = $this->getUserTujuan();

        return PoKendaraan::with([
            'po.cv',
            'supplier',
            'penerimas',
            'penerimas.pakans',
            'ptSumPaymentOnly',
            'penerimas.penerima',
        ])
            ->where('status', '!=', 'batal')
            ->whereHas('po', function ($q) use ($activeCvId, $from, $to) {
                if ($activeCvId) {
                    $q->where('cv_id', $activeCvId);
                }
                if ($from) {
                    $q->whereDate('tanggal_po', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('tanggal_po', '<=', $to);
                }
            })
            ->whereHas('penerimas.penerima', function ($q) use ($tujuans) {
                $q->whereIn('tujuan_id', $tujuans->pluck('id'));
            })
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($status, function ($q) use ($status) {
                if ($status === 'belum_dibayar') {
                    $q->where(function ($q2) {
                        $q2->whereDoesntHave('ptSumPaymentOnly')
                            ->orWhereHas('ptSumPaymentOnly', fn ($q3) => $q3->where('status', '!=', 'lunas'));
                    });
                }
                if ($status === 'sudah_dibayar') {
                    $q->whereHas('ptSumPaymentOnly', fn ($q2) => $q2->where('status', 'lunas'));
                }
            })
            ->select('po_kendaraan.*')
            ->orderBy('created_at', 'desc');
    }
}
