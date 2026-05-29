<?php

namespace App\Http\Controllers;

use App\Exports\RekapLansirExport;
use App\Models\GudangLansirHeader;
use App\Models\LansirPayment;
use App\Models\PoPenerimaLansir;
use App\Models\PurchaseOrder;
use App\Services\Datatables\RekapLansirDatatableService;
use App\Services\RekapLansirService;
use App\Traits\WithUserTujuan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapLansirController extends Controller
{
    use WithUserTujuan;
    public function __construct(
        private RekapLansirService $service,
        private RekapLansirDatatableService $datatableService,
    ) {}

    public function index(Request $request): mixed
    {
        $activeCvId = session('active_cv');
        $tujuans = $this->getUserTujuan();

        if ($request->ajax()) {
            // Gabungkan data dari PO Lansir dan Gudang Lansir
            $poLansir = PoPenerimaLansir::with(['penerima.kendaraan.po.cv', 'penerima.tujuan'])
                ->withCount('mobils')
                ->when($activeCvId, fn ($q) => $q->whereHas('penerima.kendaraan.po', fn ($q2) => $q2->where('cv_id', $activeCvId)))
                ->whereHas('penerima', function ($q) use ($tujuans) {
                    $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                })
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

            $gudangLansir = GudangLansirHeader::with(['cv', 'gudang', 'kendaraans.penerimas'])
                ->withCount('kendaraans')
                ->when($activeCvId, fn ($q) => $q->where('cv_id', $activeCvId))
                ->whereHas('kendaraans.penerimas', function ($q) use ($tujuans) {
                    $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                })
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

            $data = null;
            $tipe = null;
            $rekapMobil = collect();
            $rekapTim = collect();
            $grandTotalMobil = 0;
            $grandTotalTim = 0;
            $paymentMobil = null;
            $paymentTim = null;
            $header = null;

            // Cek apakah ini PO Lansir atau Gudang Lansir berdasarkan prefix
            if (str_starts_with($decryptedId, 'po_')) {
                // PO Lansir
                $lansirId = (int) str_replace('po_', '', $decryptedId);
                $lansir = PoPenerimaLansir::with(['penerima.kendaraan.po.cv'])->findOrFail($lansirId);
                $header = $lansir->penerima->kendaraan->po;
                $tipe = 'po';

            } elseif (str_starts_with($decryptedId, 'gudang_')) {
                // Gudang Lansir
                $gudangLansirId = (int) str_replace('gudang_', '', $decryptedId);
                $header = GudangLansirHeader::with(['cv', 'kendaraans.penerimas.pakans', 'kendaraans.penerimas.tims'])->findOrFail($gudangLansirId);
                $tipe = 'gudang';

            } else {
                // Fallback untuk backward compatibility (jika ada ID lama tanpa prefix)
                $header = PurchaseOrder::with('cv')->findOrFail($decryptedId);
                $tipe = 'po';
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Data lansir tidak ditemukan: '.$e->getMessage());
        }

        if ($tipe === 'po') {
            // Untuk PO Lansir
            $rekapMobil = $this->service->getRekapMobil($header);
            $rekapTim = $this->service->getRekapTim($header);
            $grandTotalMobil = $this->service->getGrandTotalMobil($header);
            $grandTotalTim = $this->service->getGrandTotalTim($header);

            $paymentMobil = LansirPayment::where('po_id', $header->id)
                ->where('tipe', LansirPayment::TIPE_MOBIL)->first();
            $paymentTim = LansirPayment::where('po_id', $header->id)
                ->where('tipe', LansirPayment::TIPE_TIM)->first();
        } else {
            // Untuk Gudang Lansir
            [$rekapMobil, $rekapTim, $grandTotalMobil, $grandTotalTim] = $this->prepareGudangLansirData($header);
            
            $paymentMobil = LansirPayment::where('gudang_lansir_header_id', $header->id)
                ->where('tipe', LansirPayment::TIPE_MOBIL)->first();
            $paymentTim = LansirPayment::where('gudang_lansir_header_id', $header->id)
                ->where('tipe', LansirPayment::TIPE_TIM)->first();
        }

        return view('pages.keuangan.rekap-lansir.show', compact(
            'header', 'tipe',
            'rekapMobil', 'rekapTim',
            'grandTotalMobil', 'grandTotalTim',
            'paymentMobil', 'paymentTim'
        ));
    }

    private function prepareGudangLansirData(GudangLansirHeader $gudangLansir): array
    {
        $rekapMobil = collect();
        $rekapTim = collect();
        $grandTotalMobil = 0;
        $grandTotalTim = 0;

        foreach ($gudangLansir->kendaraans as $kendaraan) {
            foreach ($kendaraan->penerimas as $penerima) {
                // Data untuk mobil (gunakan data pakan)
                $totalOngkos = 0;
                foreach ($penerima->pakans as $pakan) {
                    $totalOngkos += $pakan->jumlah_kg * $pakan->ongkos_oa;
                }

                $rekapMobil->push((object)[
                    'kendaraan' => $kendaraan,
                    'penerima' => $penerima,
                    'pakans' => $penerima->pakans,
                    'total_ongkos' => $totalOngkos,
                    'tanggal_lansir' => $gudangLansir->tanggal_lansir,
                ]);

                $grandTotalMobil += $totalOngkos;

                // Data untuk tim
                $totalUpah = 0;
                foreach ($penerima->tims as $tim) {
                    $totalUpah += $tim->total_upah;
                }

                $rekapTim->push((object)[
                    'kendaraan' => $kendaraan,
                    'penerima' => $penerima,
                    'tims' => $penerima->tims,
                    'total_berat' => $penerima->total_kg,
                    'total_upah' => $totalUpah,
                    'tanggal_lansir' => $gudangLansir->tanggal_lansir,
                ]);

                $grandTotalTim += $totalUpah;
            }
        }

        return [$rekapMobil, $rekapTim, $grandTotalMobil, $grandTotalTim];
    }

    public function bayar(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'tipe' => 'required|in:mobil,tim',
            'tipe_lansir' => 'required|in:po,gudang',
            'tanggal_bayar' => 'required|date',
            'catatan' => 'nullable|string|max:500',
        ]);

        try {
            $decryptedId = decrypt($id);
            $data = null;
            $tipeLansir = $request->tipe_lansir;
            
            if ($tipeLansir === 'po') {
                $data = PurchaseOrder::findOrFail($decryptedId);
            } else {
                $data = GudangLansirHeader::findOrFail($decryptedId);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Data lansir tidak ditemukan.');
        }

        try {
            DB::transaction(function () use ($request, $data, $tipeLansir) {
                $attributes = ['tipe' => $request->tipe];
                
                if ($tipeLansir === 'po') {
                    $attributes['po_id'] = $data->id;
                    $attributes['gudang_lansir_header_id'] = null;
                } else {
                    $attributes['gudang_lansir_header_id'] = $data->id;
                    $attributes['po_id'] = null;
                }
                
                LansirPayment::updateOrCreate(
                    $attributes,
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
