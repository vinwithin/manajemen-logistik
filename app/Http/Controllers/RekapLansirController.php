<?php

namespace App\Http\Controllers;

use App\Exports\RekapLansirExport;
use App\Models\GudangLansirHeader;
use App\Models\LansirPayment;
use App\Models\PoPenerimaLansir;
use App\Models\PurchaseOrder;
use App\Services\Datatables\RekapLansirDatatableService;
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
    public function __construct(
        private RekapLansirService $service,
        private RekapLansirDatatableService $datatableService,
    ) {}

    public function index(Request $request): mixed
    {
        $activeCvId = session('active_cv');

        if ($request->ajax()) {
            // Gabungkan data dari PO Lansir dan Gudang Lansir
            $poLansir = PoPenerimaLansir::with(['penerima.kendaraan.po.cv'])
                ->withCount('mobils')
                ->when($activeCvId, fn ($q) => $q->whereHas('penerima.kendaraan.po', fn ($q2) => $q2->where('cv_id', $activeCvId)))
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => encrypt('po_'.$item->id),
                        'tipe' => 'PO Lansir',
                        'no_referensi' => $item->penerima?->kendaraan?->po?->no_po ?? '-',
                        'tanggal_lansir' => $item->tanggal_lansir,
                        'nama_tujuan' => $item->penerima?->nama_penerima ?? '-',
                        'cv_name' => $item->penerima?->kendaraan?->po?->cv?->nama_cv ?? '-',
                        'jumlah_kendaraan' => $item->mobils_count ?? 0,
                        'original_id' => $item->id,
                    ];
                });

            $gudangLansir = GudangLansirHeader::with(['cv', 'gudang'])
                ->withCount('kendaraans')
                ->when($activeCvId, fn ($q) => $q->where('cv_id', $activeCvId))
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => encrypt('gudang_'.$item->id),
                        'tipe' => 'Gudang Lansir',
                        'no_referensi' => $item->no_lansir ?? '-',
                        'tanggal_lansir' => $item->tanggal_lansir,
                        'nama_tujuan' => $item->gudang?->nama ?? '-',
                        'cv_name' => $item->cv?->nama_cv ?? '-',
                        'jumlah_kendaraan' => $item->kendaraans_count ?? 0,
                        'original_id' => $item->id,
                    ];
                });

            // Gabungkan dan urutkan berdasarkan tanggal
            $combined = $poLansir->concat($gudangLansir)
                ->sortByDesc('tanggal_lansir')
                ->values();

            return $this->datatableService->getDataFromCollection($combined);
        }

        return view('pages.keuangan.rekap-lansir.index');
    }

    public function show(string $id): View|RedirectResponse
    {
        try {
            $decryptedId = decrypt($id);

            // Cek apakah ini PO Lansir atau Gudang Lansir berdasarkan prefix
            if (str_starts_with($decryptedId, 'po_')) {
                // PO Lansir
                $lansirId = (int) str_replace('po_', '', $decryptedId);
                $lansir = PoPenerimaLansir::with(['penerima.kendaraan.po.cv'])->findOrFail($lansirId);
                $po = $lansir->penerima->kendaraan->po;
                $tipe = 'po';

            } elseif (str_starts_with($decryptedId, 'gudang_')) {
                // Gudang Lansir
                $gudangLansirId = (int) str_replace('gudang_', '', $decryptedId);
                $gudangLansir = GudangLansirHeader::with(['cv'])->findOrFail($gudangLansirId);
                $tipe = 'gudang';

                // Untuk gudang lansir, kita perlu membuat struktur data yang berbeda
                return $this->showGudangLansir($gudangLansir);

            } else {
                // Fallback untuk backward compatibility (jika ada ID lama tanpa prefix)
                $po = PurchaseOrder::with('cv')->findOrFail($decryptedId);
                $tipe = 'po';
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Data lansir tidak ditemukan: '.$e->getMessage());
        }

        // Untuk PO Lansir
        $rekapMobil = $this->service->getRekapMobil($po);
        $rekapTim = $this->service->getRekapTim($po);
        $grandTotalMobil = $this->service->getGrandTotalMobil($po);
        $grandTotalTim = $this->service->getGrandTotalTim($po);

        $paymentMobil = LansirPayment::where('po_id', $po->id)
            ->where('tipe', LansirPayment::TIPE_MOBIL)->first();
        $paymentTim = LansirPayment::where('po_id', $po->id)
            ->where('tipe', LansirPayment::TIPE_TIM)->first();

        return view('pages.keuangan.rekap-lansir.show', compact(
            'po', 'rekapMobil', 'rekapTim',
            'grandTotalMobil', 'grandTotalTim',
            'paymentMobil', 'paymentTim', 'tipe'
        ));
    }

    private function showGudangLansir(GudangLansirHeader $gudangLansir): RedirectResponse
    {
        // Untuk gudang lansir, redirect ke halaman gudang lansir show
        return redirect()->route('gudang.lansir.show', encrypt($gudangLansir->id));
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
