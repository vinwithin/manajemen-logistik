<?php

namespace App\Http\Controllers;

use App\Exports\RekapPoExport;
use App\Models\PurchaseOrder;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class RekapPoController extends Controller
{
    public function show(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans',
                'kendaraans.penerimas.oaPayment',
            ])->findOrFail(decrypt($id));

            $allPenerimas    = $po->kendaraans->flatMap(fn($k) => $k->penerimas);
            $grandTotalOa    = $allPenerimas->sum('total_oa');
            $grandTotalPtSum = $allPenerimas->sum('total_pt_sum');

            return view('pages.keuangan.rekap-po.show', compact('po', 'grandTotalOa', 'grandTotalPtSum'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }
    }

    public function export(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans',
            ])->findOrFail(decrypt($id));

            $filename = 'Rekap-PO-'.$po->no_po.'-'.now()->format('Ymd').'.xlsx';

            return Excel::download(new RekapPoExport($po), $filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export: '.$e->getMessage());
        }
    }
}
