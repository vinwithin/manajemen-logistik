<?php

namespace App\Http\Controllers;

use App\Exports\RekapPtSumLansirExport;
use App\Models\GudangLansirHeader;
use App\Models\LansirPayment;
use App\Models\TransferPakanHeader;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class RekapPtSumLansirController extends Controller
{
    use WithUserTujuan;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of($this->rows($request))
                ->addColumn('status_bayar', function ($row) {
                    $color = $row['is_paid'] ? 'success' : 'warning';

                    return "<span class='badge bg-{$color}'>{$row['status_label']}</span>";
                })
                ->addColumn('action', function ($row) {
                    if ($row['is_paid']) {
                        return "<button type='button' class='btn btn-xs btn-success' disabled><i class='fa fa-check'></i> Sudah Dibayar</button>";
                    }

                    $url = route('keuangan.rekap-pt-sum-lansir.store-bayar', encrypt($row['key']));
                    $csrf = csrf_field();
                    $amount = number_format($row['total_pt_sum'], 0, ',', '.');

                    return "<form method='POST' action='{$url}' class='d-inline form-bayar-ptsum-lansir' data-confirm='Tandai {$row['tipe']} {$row['no_referensi']} sebesar Rp {$amount} sebagai sudah dibayar?'>{$csrf}<button type='submit' class='btn btn-xs btn-primary'><i class='fa fa-money'></i> Tandai Bayar</button></form>";
                })
                ->addIndexColumn()
                ->rawColumns(['status_bayar', 'action'])
                ->make(true);
        }

        return view('pages.keuangan.rekap-pt-sum-lansir.index');
    }

    public function storeBayar(string $id)
    {
        [$tipe, $headerId] = explode('_', decrypt($id), 2);
        $attributes = ['tipe' => LansirPayment::TIPE_PT_SUM];

        if ($tipe === 'gudang') {
            $header = GudangLansirHeader::with('kendaraans.penerimas.pakans')->findOrFail($headerId);
            $attributes['gudang_lansir_header_id'] = $header->id;
            $attributes['po_id'] = null;
            $attributes['transfer_pakan_header_id'] = null;
        } elseif ($tipe === 'transfer') {
            $header = TransferPakanHeader::with('kendaraans.penerimas.pakans')->findOrFail($headerId);
            $attributes['transfer_pakan_header_id'] = $header->id;
            $attributes['po_id'] = null;
            $attributes['gudang_lansir_header_id'] = null;
        } else {
            abort(404);
        }

        if ($header->total_pt_sum <= 0) {
            return redirect()->route('keuangan.rekap-pt-sum-lansir.index')
                ->with('error', 'Total PT Sum masih 0, tidak dapat ditandai sudah dibayar.');
        }

        LansirPayment::updateOrCreate($attributes, [
            'status' => LansirPayment::STATUS_SUDAH,
            'tanggal_bayar' => now()->toDateString(),
            'catatan' => 'Ditandai sudah dibayar dari Rekap PT Sum Lansir',
            'dibayar_oleh' => Auth::user()?->name,
        ]);

        return redirect()->route('keuangan.rekap-pt-sum-lansir.index')
            ->with('success', 'Pembayaran PT Sum Lansir berhasil ditandai sudah dibayar.');
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], [
            'from.required' => 'Dari tanggal wajib diisi untuk export Excel.',
            'to.required' => 'Sampai tanggal wajib diisi untuk export Excel.',
        ]);

        $filename = 'Rekap-PT-Sum-Lansir-' . $request->from . '-sd-' . $request->to . '.xlsx';

        return Excel::download(
            new RekapPtSumLansirExport($this->rows($request), $request->from, $request->to),
            $filename
        );
    }

    private function rows(Request $request)
    {
        $activeCvId = session('active_cv');
        $tujuanIds = $this->getUserTujuan()->pluck('id');
        $status = $request->status;

        $gudangRows = GudangLansirHeader::with(['cv', 'gudang', 'kendaraans.penerimas.pakans', 'kendaraans.penerimas.tujuan', 'lansirPayments'])
            ->when($activeCvId, fn ($q) => $q->where('cv_id', $activeCvId))
            ->when($request->from, fn ($q) => $q->whereDate('tanggal_lansir', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('tanggal_lansir', '<=', $request->to))
            ->whereHas('kendaraans.penerimas', fn ($q) => $q->whereIn('tujuan_id', $tujuanIds))
            ->get()
            ->map(fn ($header) => $this->mapGudang($header));

        $transferRows = TransferPakanHeader::with(['cv', 'tujuan', 'kendaraans.penerimas.pakans', 'kendaraans.penerimas.tujuan', 'lansirPayments'])
            ->when($activeCvId, fn ($q) => $q->where('cv_id', $activeCvId))
            ->when($request->from, fn ($q) => $q->whereDate('tanggal_transfer', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('tanggal_transfer', '<=', $request->to))
            ->whereHas('kendaraans.penerimas', fn ($q) => $q->whereIn('tujuan_id', $tujuanIds))
            ->get()
            ->map(fn ($header) => $this->mapTransfer($header));

        return $gudangRows
            ->concat($transferRows)
            ->when($status === 'belum_dibayar', fn ($rows) => $rows->where('is_paid', false))
            ->when($status === 'sudah_dibayar', fn ($rows) => $rows->where('is_paid', true))
            ->sortByDesc('tanggal_sort')
            ->values();
    }

    private function mapGudang(GudangLansirHeader $header): array
    {
        $isPaid = $header->lansirPayments
            ->firstWhere('tipe', LansirPayment::TIPE_PT_SUM)?->status === LansirPayment::STATUS_SUDAH;

        return [
            'key' => 'gudang_' . $header->id,
            'tipe' => 'Gudang',
            'no_referensi' => $header->no_lansir ?? '-',
            'tanggal' => $header->tanggal_lansir?->format('d/m/Y') ?? '-',
            'tanggal_sort' => $header->tanggal_lansir?->format('Y-m-d') ?? '',
            'cv_name' => $header->cv?->nama_cv ?? '-',
            'tujuan' => $this->tujuanList($header->kendaraans),
            'jumlah_kendaraan' => $header->kendaraans->count(),
            'total_kg' => $header->total_kg,
            'total_pt_sum' => $header->total_pt_sum,
            'is_paid' => $isPaid,
            'status_label' => $isPaid ? 'Sudah Dibayar' : 'Belum Dibayar',
        ];
    }

    private function mapTransfer(TransferPakanHeader $header): array
    {
        $isPaid = $header->lansirPayments
            ->firstWhere('tipe', LansirPayment::TIPE_PT_SUM)?->status === LansirPayment::STATUS_SUDAH;

        return [
            'key' => 'transfer_' . $header->id,
            'tipe' => 'Transfer Pakan',
            'no_referensi' => $header->no_transfer ?? '-',
            'tanggal' => $header->tanggal_transfer?->format('d/m/Y') ?? '-',
            'tanggal_sort' => $header->tanggal_transfer?->format('Y-m-d') ?? '',
            'cv_name' => $header->cv?->nama_cv ?? '-',
            'tujuan' => $this->tujuanList($header->kendaraans),
            'jumlah_kendaraan' => $header->kendaraans->count(),
            'total_kg' => $header->total_kg,
            'total_pt_sum' => $header->total_pt_sum,
            'is_paid' => $isPaid,
            'status_label' => $isPaid ? 'Sudah Dibayar' : 'Belum Dibayar',
        ];
    }

    private function tujuanList($kendaraans): string
    {
        $tujuans = $kendaraans
            ->flatMap->penerimas
            ->map(fn ($penerima) => $penerima->tujuan?->nama)
            ->filter()
            ->unique()
            ->values()
            ->join(', ');

        return $tujuans !== '' ? $tujuans : '-';
    }
}
