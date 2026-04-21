<?php

namespace App\Http\Controllers;

use App\Exports\RekapLansirExport;
use App\Models\LansirPayment;
use App\Models\PurchaseOrder;
use App\Services\RekapLansirService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapLansirController extends Controller
{
    public function __construct(private RekapLansirService $service)
    {
        // $this->middleware('permission:rekap-lansir.view')->only(['show', 'export']);
        // $this->middleware('permission:rekap-lansir.bayar')->only('bayar');
    }

    public function show(string $id): View|RedirectResponse
    {
        try {
            $po = PurchaseOrder::with('cv')->findOrFail(decrypt($id));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }

        $rekapMobil = $this->service->getRekapMobil($po);
        $rekapTim = $this->service->getRekapTim($po)->load('mobils');
        $grandTotalMobil = $this->service->getGrandTotalMobil($po);
        $grandTotalTim = $this->service->getGrandTotalTim($po);

        $paymentMobil = LansirPayment::where('po_id', $po->id)
            ->where('tipe', LansirPayment::TIPE_MOBIL)->first();
        $paymentTim = LansirPayment::where('po_id', $po->id)
            ->where('tipe', LansirPayment::TIPE_TIM)->first();

        return view('pages.keuangan.rekap-lansir.show', compact(
            'po', 'rekapMobil', 'rekapTim',
            'grandTotalMobil', 'grandTotalTim',
            'paymentMobil', 'paymentTim'
        ));
    }

    public function bayar(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'tipe' => 'required|in:mobil,tim',
            'tanggal_bayar' => 'required|date',
            'catatan' => 'nullable|string|max:500',
        ]);

        try {
            $po = PurchaseOrder::findOrFail(decrypt($id));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }

        try {
            DB::transaction(function () use ($request, $po) {
                LansirPayment::updateOrCreate(
                    ['po_id' => $po->id, 'tipe' => $request->tipe],
                    [
                        'status' => LansirPayment::STATUS_SUDAH,
                        'tanggal_bayar' => $request->tanggal_bayar,
                        'catatan' => $request->catatan,
                        'dibayar_oleh' => Auth::user()->name,
                    ]
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan pembayaran: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function export(string $id): BinaryFileResponse|RedirectResponse
    {
        try {
            $po = PurchaseOrder::with('cv')->findOrFail(decrypt($id));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }

        // try {
            $filename = 'rekap-lansir-'.$po->no_po.'-'.now()->format('Ymd').'.xlsx';

            return Excel::download(new RekapLansirExport($po), $filename);
        // } catch (\Exception $e) {
        //     return redirect()->back()->with('error', 'Gagal mengekspor data: '.$e->getMessage());
        // }
    }
}
