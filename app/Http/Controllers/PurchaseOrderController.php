<?php

namespace App\Http\Controllers;

use App\Exports\ExportToPT;
use App\Exports\PurchaseOrderExport;
use App\Exports\PurchaseOrderPeriodExport;
use App\Models\Cv;
use App\Models\KodePakan;
use App\Models\Mobil;
use App\Models\OaPayment;
use App\Models\Penerima;
use App\Models\PoItemLansir;
use App\Models\PoKendaraan;
use App\Models\PoLansirMobil;
use App\Models\PoLansirTim;
use App\Models\PoPenerima;
use App\Models\PoPenerimaLansir;
use App\Models\PoPenerimaPakan;
use App\Models\PoPeriodeDokumen;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Tujuan;
use App\Services\Datatables\PurchaseOrderService;
use App\Services\GudangStokService;
use App\Services\PoKendaraanIdtrackSpjService;
use App\Traits\WithUserTujuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    use WithUserTujuan;

    protected $poService;

    protected $gudangStokService;

    protected PoKendaraanIdtrackSpjService $poKendaraanIdtrackSpj;

    public function __construct(
        PurchaseOrderService $poService,
        GudangStokService $gudangStokService,
        PoKendaraanIdtrackSpjService $poKendaraanIdtrackSpj,
    ) {
        $this->poService = $poService;
        $this->gudangStokService = $gudangStokService;
        $this->poKendaraanIdtrackSpj = $poKendaraanIdtrackSpj;
    }

    public function export(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], [
            'from.required' => 'Tanggal awal periode wajib diisi.',
            'to.required' => 'Tanggal akhir periode wajib diisi.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;

        $filename = 'PO-Periode-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new PurchaseOrderPeriodExport($from, $to, $cvId), $filename);
    }

    public function exportToPT(string $id)
    {
        try {
            $po = PurchaseOrder::with(['cv', 'items.tujuan', 'items.supplier', 'items.penerimaList'])
                ->findOrFail(decrypt($id));

            $filename = 'PO-' . $po->no_po . '-' . now()->format('Ymd') . '.xlsx';

            return Excel::download(new ExportToPT($po), $filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export: ' . $e->getMessage());
        }
    }

    public function exportPo(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            $filename = 'PO-' . $po->no_po . '-' . now()->format('Ymd') . '.xlsx';

            return Excel::download(new PurchaseOrderExport($po), $filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export: ' . $e->getMessage());
        }
    }

    public function exportPoPdf(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            // Semua kode pakan dari master
            $kodePakanList = KodePakan::orderBy('kode')->get();

            $pdf = Pdf::loadView('pdf.purchase-order', compact('po', 'kodePakanList'))
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'PO-' . $po->no_po . '-' . now()->format('Ymd') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    public function exportPoPdfSupplier(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            // Semua kode pakan dari master
            $kodePakanList = KodePakan::orderBy('kode')->get();

            $pdf = Pdf::loadView('pdf.purchase-order-supplier', compact('po', 'kodePakanList'))
                ->setPaper('legal', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'PO-' . $po->no_po . '-Supplier-' . now()->format('Ymd') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export PDF Supplier: ' . $e->getMessage());
        }
    }

    public function exportPoPdfPtSum(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            // Semua kode pakan dari master
            $kodePakanList = KodePakan::orderBy('kode')->get();

            $pdf = Pdf::loadView('pdf.purchase-order-ptsum', compact('po', 'kodePakanList'))
                ->setPaper('legal', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'PO-' . $po->no_po . '-PTSum-' . now()->format('Ymd') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export PDF PT Sum: ' . $e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $cvId = $request->cv_id ?? session('active_cv');
            $from = $request->from;
            $to = $request->to;

            // Query PO berdasarkan filter
            $query = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->orderBy('tanggal_po', 'asc')->orderBy('no_po', 'asc');

            if ($from) {
                $query->whereDate('tanggal_po', '>=', $from);
            }

            if ($to) {
                $query->whereDate('tanggal_po', '<=', $to);
            }

            if ($cvId) {
                $query->where('cv_id', $cvId);
            }

            $pos = $query->get();

            // Semua kode pakan dari master
            $kodePakanList = KodePakan::orderBy('kode')->get();

            $pdf = Pdf::loadView('pdf.purchase-order-period', compact('pos', 'kodePakanList', 'from', 'to'))
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'PO-Periode-' . now()->format('Ymd-His') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    public function exportPdfSupplierConfirm(Request $request)
    {
        $supplierList = Supplier::orderBy('nama')->get();
        $tujuans = $this->getUserTujuan();

        $cvId = $request->cv_id;   // opsional
        $supplierId = $request->supplier_id; // opsional
        $tujuanId = $request->tujuan_id; // opsional
        $from = $request->from;
        $to = $request->to;
        $poCount = null;

        if ($from && $to) {
            $query = PurchaseOrder::query();
            if ($cvId) {
                $query->where('cv_id', $cvId);
            }
            if ($supplierId) {
                $query->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
            }
            if ($tujuanId) {
                $query->whereHas('kendaraans.penerimas', fn($q) => $q->where('tujuan_id', $tujuanId));
            }
            $poCount = $query->whereDate('tanggal_po', '>=', $from)
                ->whereDate('tanggal_po', '<=', $to)
                ->count();
        }

        return view('pages.purchase-order.export-supplier-confirm', compact(
            'supplierList',
            'tujuans',
            'cvId',
            'supplierId',
            'tujuanId',
            'from',
            'to',
            'poCount'
        ));
    }

    public function exportPdfSupplier(Request $request)
    {
        try {
            $request->validate(['from' => 'required|date', 'to' => 'required|date']);

            $cvId = $request->cv_id;
            $supplierId = $request->supplier_id;
            $tujuanId = $request->tujuan_id;
            $from = $request->from;
            $to = $request->to;

            $query = PurchaseOrder::with([
                'cv',
                'kendaraans' => function ($q) {
                    $q->where('status', '!=', 'batal');
                },
                'kendaraans.supplier',
                'kendaraans.oaPayments',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->orderBy('tanggal_po', 'asc')->orderBy('no_po', 'asc');

            $query->whereDate('tanggal_po', '>=', $from)
                ->whereDate('tanggal_po', '<=', $to);

            if ($cvId) {
                $query->where('cv_id', $cvId);
            }
            if ($supplierId) {
                $query->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
            }
            if ($tujuanId) {
                $query->whereHas('kendaraans.penerimas', fn($q) => $q->where('tujuan_id', $tujuanId));
            }

            $pos = $query->get();

            $pdf = Pdf::loadView('pdf.purchase-order-period-supplier', compact('pos', 'from', 'to'))
                ->setPaper('legal', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'PO-Periode-Supplier-' . now()->format('Ymd-His') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export PDF Supplier: ' . $e->getMessage());
        }
    }

    public function exportPdfPtSumConfirm(Request $request)
    {
        $suppliers = Supplier::orderBy('nama')->get();
        $tujuans = $this->getUserTujuan();

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $supplierId = $request->supplier_id;
        $tujuanIds = $request->tujuan_ids
            ? array_filter(array_map('intval', explode(',', $request->tujuan_ids)))
            : [];
        $selectedKendaraanIds = $request->kendaraan_ids ? array_filter(explode(',', $request->kendaraan_ids)) : [];
        $poCount = null;
        $cvNama = null;
        $dokumen = null;
        $noSuratSuggest = null;
        $kendaraanList = collect();

        if ($cvId && $from && $to) {
            $query = PurchaseOrder::where('cv_id', $cvId)
                ->whereDate('tanggal_po', '>=', $from)
                ->whereDate('tanggal_po', '<=', $to);

            if ($supplierId) {
                $query->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
            }

            if (! empty($tujuanIds)) {
                $query->whereHas('kendaraans.penerimas', fn($q) => $q->whereIn('tujuan_id', $tujuanIds));
            }

            $poCount = $query->count();

            $kendaraanList = PoKendaraan::whereHas('po', function ($q) use ($cvId, $from, $to) {
                $q->where('cv_id', $cvId)
                    ->whereDate('tanggal_po', '>=', $from)
                    ->whereDate('tanggal_po', '<=', $to);
            })
                ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
                ->when(! empty($tujuanIds), fn($q) => $q->whereHas('penerimas', fn($q2) => $q2->whereIn('tujuan_id', $tujuanIds)))
                ->where('status', '!=', 'batal')
                ->with('po')
                ->orderBy('no_polisi')
                ->get(['id', 'no_polisi', 'po_id', 'status']);

            $cv = Cv::find($cvId);
            $cvNama = $cv?->nama_cv;

            $dokumen = PoPeriodeDokumen::where('cv_id', $cvId)
                ->where('dari', $from)
                ->where('sampai', $to)
                ->where('tipe', 'ptsum')
                ->first();

            if (! $dokumen && $cv) {
                $generated = PoPeriodeDokumen::generateNoSurat($cv, 'ptsum', $from);
                $noSuratSuggest = $generated['no_surat'];
            }
        }

        return view('pages.purchase-order.export-ptsum-confirm', compact(
            'suppliers',
            'tujuans',
            'cvId',
            'from',
            'to',
            'supplierId',
            'tujuanIds',
            'poCount',
            'cvNama',
            'dokumen',
            'noSuratSuggest',
            'kendaraanList',
            'selectedKendaraanIds'
        ));
    }

    public function exportPdfPtSum(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'tujuan_ids' => 'required|string',
        ], [
            'from.required' => 'Tanggal awal periode wajib diisi.',
            'to.required' => 'Tanggal akhir periode wajib diisi.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            'tujuan_ids.required' => 'Pilih tujuan terlebih dahulu.',
        ]);

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $supplierId = $request->supplier_id;
        // Parse tujuan_ids: bisa "6" (single) atau "6,8" (gabungan)
        $tujuanIds = array_filter(array_map('intval', explode(',', $request->tujuan_ids)));
        $noSuratInput = $request->no_surat;
        $tanggalSurat = $request->tanggal_surat;
        $cpi = $request->cpi;
        $kendaraanIds = $request->kendaraan_ids
            ? array_filter(array_map('intval', explode(',', $request->kendaraan_ids)))
            : [];

        if (! $cvId) {
            return redirect()->route('purchase-order.export-ptsum-confirm')
                ->with('error', 'Pilih CV terlebih dahulu.');
        }

        $cv = Cv::find($cvId);
        $noSurat = null;

        // Ambil tujuan pertama untuk tipe dokumen
        $tujuanPrimary = Tujuan::find($tujuanIds[0] ?? null);
        $tujuanTypeMap = [
            'co_farm' => 'CFJ',
            'rent_farm' => 'RFJ',
            'gudang' => 'GJ',
            'direct' => 'DRC',
        ];
        $tujuanType = $tujuanTypeMap[$tujuanPrimary?->type] ?? 'DRC';

        if ($noSuratInput && $from && $to && $cv) {
            $dokumen = DB::transaction(function () use ($cvId, $from, $to, $cv, $request, $noSuratInput, $cpi) {
                $existing = PoPeriodeDokumen::where('cv_id', $cvId)
                    ->where('dari', $from)
                    ->where('sampai', $to)
                    ->where('tipe', 'ptsum')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'no_surat' => $noSuratInput,
                        'cpi' => $cpi,
                        'catatan' => $request->catatan,
                    ]);

                    return $existing;
                }

                $generated = PoPeriodeDokumen::generateNoSurat($cv, 'ptsum', $from);

                return PoPeriodeDokumen::create([
                    'cv_id' => $cvId,
                    'dari' => $from,
                    'sampai' => $to,
                    'tipe' => 'ptsum',
                    'urutan' => $generated['urutan'],
                    'cpi' => $cpi,
                    'no_surat' => $noSuratInput,
                    'catatan' => $request->catatan,
                    'created_by' => Auth::user()->id,
                ]);
            });

            $noSurat = $dokumen->no_surat;
        }

        $query = PurchaseOrder::with([
            'cv',
            'kendaraans' => function ($q) {
                $q->where('status', '!=', 'batal');
            },
            'kendaraans.supplier',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
        ])->orderBy('tanggal_po', 'asc')->orderBy('no_po', 'asc');

        $query->whereDate('tanggal_po', '>=', $from)
            ->whereDate('tanggal_po', '<=', $to);

        if ($cvId) {
            $query->where('cv_id', $cvId);
        }

        // Filter supplier
        if ($supplierId) {
            $query->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
        }

        // Filter tujuan (mendukung multiple ID)
        if (! empty($tujuanIds)) {
            $query->whereHas('kendaraans.penerimas', fn($q) => $q->whereIn('tujuan_id', $tujuanIds));
        }

        // Filter kendaraan spesifik (plat mobil)
        if (! empty($kendaraanIds)) {
            $query->whereHas('kendaraans', fn($q) => $q->whereIn('id', $kendaraanIds));
        }

        $pos = $query->get();

        // Filter penerima berdasarkan tujuan jika ada filter
        if (! empty($tujuanIds)) {
            foreach ($pos as $po) {
                foreach ($po->kendaraans as $kendaraan) {
                    $kendaraan->setRelation('penerimas', $kendaraan->penerimas->filter(
                        fn($p) => in_array($p->tujuan_id, $tujuanIds)
                    )->values());
                }
                // Hapus kendaraan yang tidak punya penerima setelah filter
                $po->setRelation('kendaraans', $po->kendaraans->filter(
                    fn($k) => $k->penerimas->isNotEmpty()
                )->values());
            }
            // Hapus PO yang tidak punya kendaraan setelah filter
            $pos = $pos->filter(fn($po) => $po->kendaraans->isNotEmpty())->values();
        }

        // Jika ada filter kendaraan spesifik, filter kendaraan yang ditampilkan
        if (! empty($kendaraanIds)) {
            foreach ($pos as $po) {
                $po->setRelation('kendaraans', $po->kendaraans->filter(
                    fn($k) => in_array($k->id, $kendaraanIds)
                )->values());
            }
        }

        // Nama tujuan untuk dokumen — gabungkan jika multiple
        $tujuanNamaList = Tujuan::whereIn('id', $tujuanIds)->pluck('nama')->join(' & ');
        $tujuanNama = $cpi ?? $tujuanNamaList;

        $pdf = Pdf::loadView('pdf.purchase-order-period-ptsum', compact('pos', 'from', 'to', 'noSurat', 'tujuanNama', 'tanggalSurat'))
            ->setPaper('legal', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $cvNama = $pos->first()?->cv?->nama_cv ?? 'CV';
        $filename = 'PO-Periode-PTSum-' . str_replace(' ', '-', $cvNama) . '-' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
        // } catch (Exception $e) {
        //     return redirect()->back()->with('error', 'Gagal export PDF PT Sum: ' . $e->getMessage());
        // }
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $activeCvId = session('active_cv');
            $tujuans = $this->getUserTujuan();

            $query = PurchaseOrder::with(['cv', 'kendaraans', 'kendaraans.penerimas.penerima'])
                ->withCount('kendaraans')
                ->orderBy('tanggal_po', 'desc')
                ->where(function ($q) use ($tujuans) {
                    $q->whereHas('kendaraans.penerimas.penerima', function ($q) use ($tujuans) {
                        $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                    })
                        ->orWhereDoesntHave('kendaraans.penerimas')        // kendaraan tanpa penerima
                        ->orWhereHas('kendaraans.penerimas', function ($q) {
                            $q->whereDoesntHave('penerima');               // penerima orphan
                        });
                });

            if ($activeCvId) {
                $query->where('cv_id', $activeCvId);
            }

            return $this->poService->getData($query);
        }

        return view('pages.purchase-order.index');
    }

    public function create()
    {
        $activeCvId = session('active_cv');

        // Gunakan trait untuk mengambil userTujuan
        $userTujuan = $this->getUserTujuan();

        $suppliers = Supplier::orderBy('nama')->get();
        $kodePakans = KodePakan::orderBy('kode')->get();
        $tujuans = $userTujuan;
        $penerimas = Penerima::with('tujuan')
            ->where('is_aktif', true)
            ->whereIn('tujuan_id', $userTujuan->pluck('id'))
            ->orderBy('nama')
            ->get(['id', 'nama', 'tujuan_id']);
        $mobils = Mobil::where('is_aktif', true)
            ->orderBy('nopol')
            ->get(['id', 'nopol', 'nama_sopir', 'no_hp']);
        $batasOmzet = Cv::BATAS_OMZET;

        // $userCvs dan $activeCv sudah tersedia di semua view dari AppServiceProvider
        return view('pages.purchase-order.create', compact('activeCvId', 'suppliers', 'kodePakans', 'tujuans', 'penerimas', 'mobils', 'batasOmzet'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'no_po' => 'required|string|max:100|unique:purchase_orders,no_po',
            'tanggal_po' => 'required|date',
            'cv_id' => 'nullable|exists:cv,id',
            'catatan' => 'nullable|string',
            'kendaraan' => 'required|array|min:1',
            'kendaraan.*.no_polisi' => 'required|string|max:20',
            'kendaraan.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraan.*.no_hp' => 'nullable|string|max:20',
            'kendaraan.*.supplier_id' => 'nullable|exists:suppliers,id',
            'kendaraan.*.jenis_kendaraan' => 'nullable|string|max:100',
            'kendaraan.*.tujuan_id' => 'required|exists:tujuan,id',
            'kendaraan.*.jumlah_kg' => 'nullable|string|max:100',
            'kendaraan.*.jumlah_karung' => 'nullable|string|max:100',
            'kendaraan.*.ongkos_angkut' => 'nullable|numeric|min:0',

            // Validasi DP
            'kendaraan.*.dp_nominal' => 'nullable|numeric|min:0',
            'kendaraan.*.dp_persen' => 'nullable|numeric|min:0|max:100',
            'kendaraan.*.dp_tanggal' => 'nullable|date',
            'kendaraan.*.dp_metode' => 'nullable|string|in:transfer,tunai,giro',
            'kendaraan.*.dp_keterangan' => 'nullable|string|max:500',

            'kendaraan.*.penerima' => 'nullable|array|min:0',
            'kendaraan.*.penerima.*.penerima_id' => 'nullable|exists:penerima,id',
            'kendaraan.*.penerima.*.nama_penerima' => 'nullable|string|max:255',
            'kendaraan.*.penerima.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraan.*.penerima.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraan.*.penerima.*.pakans' => 'nullable|array|min:0',
            'kendaraan.*.penerima.*.pakans.*.kode_pakan_id' => 'nullable|exists:kode_pakan,id',
            'kendaraan.*.penerima.*.pakans.*.jumlah_kg' => 'nullable|numeric|min:0.01',
            'kendaraan.*.penerima.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'kendaraan.*.penerima.*.pakans.*.harga_pt_sum' => 'nullable|numeric|min:0',
        ], [
            'no_po.required' => 'Nomor PO wajib diisi.',
            'no_po.string' => 'Nomor PO harus berupa teks.',
            'no_po.max' => 'Nomor PO maksimal 100 karakter.',
            'no_po.unique' => 'Nomor PO sudah digunakan.',
            'tanggal_po.required' => 'Tanggal PO wajib diisi.',
            'tanggal_po.date' => 'Tanggal PO harus berupa tanggal yang valid.',
            'cv_id.exists' => 'CV yang dipilih tidak valid.',
            'kendaraan.required' => 'Minimal satu kendaraan wajib ditambahkan.',
            'kendaraan.array' => 'Data kendaraan harus berupa array.',
            'kendaraan.min' => 'Minimal satu kendaraan wajib ditambahkan.',
            'kendaraan.*.no_polisi.required' => 'Nomor polisi kendaraan wajib diisi.',
            'kendaraan.*.no_polisi.string' => 'Nomor polisi harus berupa teks.',
            'kendaraan.*.no_polisi.max' => 'Nomor polisi maksimal 20 karakter.',
            'kendaraan.*.nama_sopir.string' => 'Nama sopir harus berupa teks.',
            'kendaraan.*.nama_sopir.max' => 'Nama sopir maksimal 255 karakter.',
            'kendaraan.*.no_hp.string' => 'No HP sopir harus berupa teks.',
            'kendaraan.*.no_hp.max' => 'No HP sopir maksimal 20 karakter.',
            'kendaraan.*.supplier_id.exists' => 'Supplier yang dipilih tidak valid.',
            'kendaraan.*.jenis_kendaraan.string' => 'Jenis kendaraan harus berupa teks.',
            'kendaraan.*.jenis_kendaraan.max' => 'Jenis kendaraan maksimal 100 karakter.',
            'kendaraan.*.tujuan_id.required' => 'Tujuan kendaraan wajib dipilih.',
            'kendaraan.*.tujuan_id.exists' => 'Tujuan yang dipilih tidak valid.',
            'kendaraan.*.jumlah_kg.string' => 'Jumlah kg harus berupa teks.',
            'kendaraan.*.jumlah_kg.max' => 'Jumlah kg maksimal 100 karakter.',
            'kendaraan.*.jumlah_karung.string' => 'Jumlah karung harus berupa teks.',
            'kendaraan.*.jumlah_karung.max' => 'Jumlah karung maksimal 100 karakter.',
            'kendaraan.*.ongkos_angkut.numeric' => 'Ongkos angkut harus berupa angka.',
            'kendaraan.*.ongkos_angkut.min' => 'Ongkos angkut tidak boleh kurang dari 0.',
            'kendaraan.*.dp_nominal.numeric' => 'Nominal DP harus berupa angka.',
            'kendaraan.*.dp_nominal.min' => 'Nominal DP tidak boleh kurang dari 0.',
            'kendaraan.*.dp_persen.numeric' => 'Persen DP harus berupa angka.',
            'kendaraan.*.dp_persen.min' => 'Persen DP tidak boleh kurang dari 0.',
            'kendaraan.*.dp_persen.max' => 'Persen DP tidak boleh lebih dari 100.',
            'kendaraan.*.dp_tanggal.date' => 'Tanggal DP harus berupa tanggal yang valid.',
            'kendaraan.*.dp_metode.string' => 'Metode DP harus berupa teks.',
            'kendaraan.*.dp_metode.in' => 'Metode DP harus berupa transfer, tunai, atau giro.',
            'kendaraan.*.dp_keterangan.string' => 'Keterangan DP harus berupa teks.',
            'kendaraan.*.dp_keterangan.max' => 'Keterangan DP maksimal 500 karakter.',
            'kendaraan.*.penerima.array' => 'Data penerima harus berupa array.',
            'kendaraan.*.penerima.*.penerima_id.exists' => 'Penerima yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.nama_penerima.string' => 'Nama penerima harus berupa teks.',
            'kendaraan.*.penerima.*.nama_penerima.max' => 'Nama penerima maksimal 255 karakter.',
            'kendaraan.*.penerima.*.tujuan_id.exists' => 'Tujuan penerima yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.no_surat_jalan.string' => 'Nomor surat jalan harus berupa teks.',
            'kendaraan.*.penerima.*.no_surat_jalan.max' => 'Nomor surat jalan maksimal 100 karakter.',
            'kendaraan.*.penerima.*.pakans.array' => 'Data pakan harus berupa array.',
            'kendaraan.*.penerima.*.pakans.*.kode_pakan_id.exists' => 'Kode pakan yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.pakans.*.jumlah_kg.numeric' => 'Jumlah kg pakan harus berupa angka.',
            'kendaraan.*.penerima.*.pakans.*.jumlah_kg.min' => 'Jumlah kg pakan tidak boleh kurang dari 0.01.',
            'kendaraan.*.penerima.*.pakans.*.ongkos_oa.numeric' => 'Ongkos OA harus berupa angka.',
            'kendaraan.*.penerima.*.pakans.*.ongkos_oa.min' => 'Ongkos OA tidak boleh kurang dari 0.',
            'kendaraan.*.penerima.*.pakans.*.harga_pt_sum.numeric' => 'Harga PT Sum harus berupa angka.',
            'kendaraan.*.penerima.*.pakans.*.harga_pt_sum.min' => 'Harga PT Sum tidak boleh kurang dari 0.',
        ]);

        // Validasi cv melebihi batas omzet
        if ($request->cv_id) {
            $cv = Cv::find($request->cv_id);
            if ($cv && $cv->isMelebihiBatas()) {
                return redirect()->back()
                    ->with('error', 'CV yang dipilih sudah melebihi batas omzet tahunan dan tidak dapat dipilih.')
                    ->withInput();
            }
        }

        // dd($request->all());

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::create([
                'no_po' => strtoupper($request->no_po),
                'tanggal_po' => $request->tanggal_po,
                'cv_id' => $request->cv_id,
                'catatan' => $request->catatan,
                'status' => PurchaseOrder::STATUS_DRAFT,
            ]);

            foreach ($request->kendaraan as $kendaraanData) {
                $kendaraan = PoKendaraan::create([
                    'po_id' => $po->id,
                    'no_polisi' => strtoupper(trim($kendaraanData['no_polisi'])),
                    'nama_sopir' => $kendaraanData['nama_sopir'] ?? null,
                    'no_hp' => $kendaraanData['no_hp'] ?? null,
                    'supplier_id' => $kendaraanData['supplier_id'] ?? null,
                    'jenis_kendaraan' => $kendaraanData['jenis_kendaraan'] ?? null,
                    'tujuan_id' => $kendaraanData['tujuan_id'] ?? null,
                    'ongkos_angkut' => $kendaraanData['ongkos_angkut'] ?? 0,
                    'jumlah_kg' => $kendaraanData['jumlah_kg'] ?? null,
                    'jumlah_karung' => isset($kendaraanData['jumlah_kg']) && $kendaraanData['jumlah_kg'] > 0
                        ? (int) ceil($kendaraanData['jumlah_kg'] / 50)
                        : null,
                    'status' => 'pending',
                    // Data DP
                    'dp_nominal' => $kendaraanData['dp_nominal'] ?? 0,
                    'dp_persen' => $kendaraanData['dp_persen'] ?? null,
                    'dp_tanggal' => $kendaraanData['dp_tanggal'] ?? null,
                    'dp_metode' => $kendaraanData['dp_metode'] ?? null,
                    'dp_keterangan' => $kendaraanData['dp_keterangan'] ?? null,
                ]);

                $totalTagihanKendaraan = 0;

                foreach ($kendaraanData['penerima'] ?? [] as $penerimaData) {
                    if (empty(trim($penerimaData['nama_penerima'] ?? ''))) {
                        continue;
                    }

                    $penerima = PoPenerima::create([
                        'po_kendaraan_id' => $kendaraan->id,
                        'penerima_id' => $penerimaData['penerima_id'] ?? null,
                        'nama_penerima' => $penerimaData['nama_penerima'],
                        'tujuan_id' => $penerimaData['tujuan_id'] ?? null,
                        'no_do' => $penerimaData['no_surat_jalan'] ?? null,
                        'status' => 'pending',
                    ]);

                    // Hitung total tagihan OA untuk penerima ini
                    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                        // Skip pakan yang kode_pakan_id atau jumlah_kg-nya kosong
                        if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                            continue;
                        }

                        PoPenerimaPakan::create([
                            'po_penerima_id' => $penerima->id,
                            'kode_pakan_id' => $pakanData['kode_pakan_id'],
                            'jumlah_kg' => $pakanData['jumlah_kg'],
                            'ongkos_oa' => $pakanData['ongkos_oa'] ?? 0,
                            'harga_pt_sum' => $pakanData['harga_pt_sum'] ?? 0,
                        ]);

                        // Akumulasi total tagihan OA untuk kendaraan
                        $totalTagihanKendaraan += ($pakanData['jumlah_kg'] ?? 0) * ($pakanData['ongkos_oa'] ?? 0);
                    }
                }

                if ($totalTagihanKendaraan > 0) {
                    $dpNominal = (! empty($kendaraanData['dp_nominal']) && $kendaraanData['dp_nominal'] > 0)
                        ? $kendaraanData['dp_nominal']
                        : 0;
                    $dpTanggal = $kendaraanData['dp_tanggal'] ?? null;
                    $dpMetode = $kendaraanData['dp_metode'] ?? null;
                    $dpKeterangan = $kendaraanData['dp_keterangan'] ?? null;

                    $jumlahBayar = $dpNominal;
                    $tanggalBayar = $dpNominal > 0 ? ($dpTanggal ?? now()) : null;
                    $metodeBayar = $dpNominal > 0 ? ($dpMetode ?? 'transfer') : null;

                    $keterangan = 'Pembayaran OA - Kendaraan ' . $kendaraan->no_polisi . ' (PO: ' . $po->no_po . ')';
                    if ($dpNominal > 0 && $dpKeterangan) {
                        $keterangan .= ' | DP: ' . $dpKeterangan;
                    }

                    $status = 'pending';
                    if ($dpNominal > 0) {
                        $status = $dpNominal >= $totalTagihanKendaraan ? 'lunas' : 'partial';
                    }

                    OaPayment::create([
                        'po_kendaraan_id' => $kendaraan->id,
                        'po_penerima_id' => null, // NULL karena ini per kendaraan, bukan per penerima
                        'supplier_id' => $kendaraan->supplier_id,
                        'tipe_pembayaran' => 'dp_supplier',
                        'jumlah_tagihan' => $totalTagihanKendaraan,
                        'jumlah_bayar' => $jumlahBayar,
                        'tanggal_bayar' => $tanggalBayar,
                        'metode_bayar' => $metodeBayar,
                        'keterangan' => $keterangan,
                        'status' => $status,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('purchase-order.edit', encrypt($po->id))
                ->with('success', 'PO berhasil dibuat.');
        } catch (QueryException $e) {
            DB::rollBack();

            // Log error detail untuk debugging
            Log::error('QueryException saat store PO', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
            ]);

            if (str_contains($e->getMessage(), 'Unknown column') || str_contains($e->getMessage(), 'dp_nominal')) {
                return redirect()->back()
                    ->with('error', 'Database belum diupdate. Silakan jalankan migration terlebih dahulu: php artisan migrate')
                    ->withInput();
            }

            return redirect()->back()
                ->with('error', 'Gagal menyimpan PO: ' . $e->getMessage())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Exception saat store PO', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Gagal menyimpan PO: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
                'kendaraans.penerimas.penerima.tujuan',
                'kendaraans.penerimas.oaPayment',
            ])->findOrFail(decrypt($id));

            // Kode pakan unik dalam PO untuk kolom pivot
            $kodePakanList = $po->kendaraans
                ->flatMap(fn($k) => $k->penerimas)
                ->flatMap(fn($p) => $p->pakans)
                ->map(fn($pk) => $pk->kodePakan)
                ->filter()
                ->unique('id')
                ->sortBy('kode')
                ->values();

            return view('pages.purchase-order.show', compact('po', 'kodePakanList'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }
    }

    public function edit(string $id)
    {
        try {
            $po = PurchaseOrder::with([
                'cv',
                'kendaraans.supplier',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            $cvList = Cv::withOmzet();
            $tujuans = $this->getUserTujuan();
            $suppliers = Supplier::orderBy('nama')->get();
            $kodePakans = KodePakan::orderBy('kode')->get();
            $penerimas = Penerima::with('tujuan')
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'tujuan_id']);
            $mobils = Mobil::where('is_aktif', true)
                ->orderBy('nopol')
                ->get(['id', 'nopol', 'nama_sopir', 'no_hp']);
            $batasOmzet = Cv::BATAS_OMZET;

            return view('pages.purchase-order.sunting', compact('po', 'cvList', 'tujuans', 'suppliers', 'kodePakans', 'penerimas', 'mobils', 'batasOmzet'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'no_po' => 'nullable|string|max:100',
            'tanggal_po' => 'nullable|date',
            'catatan' => 'nullable|string',
            'cv_id' => 'nullable|exists:cv,id',
            'kendaraan' => 'required|array|min:1',
            'kendaraan.*.id' => 'nullable|exists:po_kendaraan,id',
            'kendaraan.*.no_polisi' => 'required|string|max:20',
            'kendaraan.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraan.*.no_hp' => 'nullable|string|max:20',
            'kendaraan.*.supplier_id' => 'nullable|exists:suppliers,id',
            'kendaraan.*.jenis_kendaraan' => 'nullable|string|max:100',
            'kendaraan.*.tujuan_id' => 'required|exists:tujuan,id',
            'kendaraan.*.jumlah_kg' => 'nullable|numeric|min:0',
            'kendaraan.*.ongkos_angkut' => 'nullable|numeric|min:0',
            'kendaraan.*.status' => 'nullable|in:pending,berangkat,selesai,batal',
            // Validasi DP
            'kendaraan.*.dp_nominal' => 'nullable|numeric|min:0',
            'kendaraan.*.dp_persen' => 'nullable|numeric|min:0|max:100',
            'kendaraan.*.dp_tanggal' => 'nullable|date',
            'kendaraan.*.dp_metode' => 'nullable|string|in:transfer,tunai,giro',
            'kendaraan.*.dp_keterangan' => 'nullable|string|max:500',
            'kendaraan.*.penerima' => 'nullable|array',
            'kendaraan.*.penerima.*.id' => 'nullable|exists:po_penerima,id',
            'kendaraan.*.penerima.*.penerima_id' => 'nullable|exists:penerima,id',
            'kendaraan.*.penerima.*.nama_penerima' => 'nullable|string|max:255',
            'kendaraan.*.penerima.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraan.*.penerima.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraan.*.penerima.*.status' => 'nullable|in:pending,berangkat,tiba,selesai,batal',
            'kendaraan.*.penerima.*.pakans' => 'nullable|array',
            'kendaraan.*.penerima.*.pakans.*.kode_pakan_id' => 'nullable|exists:kode_pakan,id',
            'kendaraan.*.penerima.*.pakans.*.jumlah_kg' => 'nullable|numeric|min:0.01',
            'kendaraan.*.penerima.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'kendaraan.*.penerima.*.pakans.*.harga_pt_sum' => 'nullable|numeric|min:0',
        ], [
            'no_po.string' => 'Nomor PO harus berupa teks.',
            'no_po.max' => 'Nomor PO maksimal 100 karakter.',
            'tanggal_po.date' => 'Tanggal PO harus berupa tanggal yang valid.',
            'cv_id.exists' => 'CV yang dipilih tidak valid.',
            'kendaraan.required' => 'Minimal satu kendaraan wajib ditambahkan.',
            'kendaraan.array' => 'Data kendaraan harus berupa array.',
            'kendaraan.min' => 'Minimal satu kendaraan wajib ditambahkan.',
            'kendaraan.*.id.exists' => 'Kendaraan yang dipilih tidak valid.',
            'kendaraan.*.no_polisi.required' => 'Nomor polisi kendaraan wajib diisi.',
            'kendaraan.*.no_polisi.string' => 'Nomor polisi harus berupa teks.',
            'kendaraan.*.no_polisi.max' => 'Nomor polisi maksimal 20 karakter.',
            'kendaraan.*.nama_sopir.string' => 'Nama sopir harus berupa teks.',
            'kendaraan.*.nama_sopir.max' => 'Nama sopir maksimal 255 karakter.',
            'kendaraan.*.no_hp.string' => 'No HP sopir harus berupa teks.',
            'kendaraan.*.no_hp.max' => 'No HP sopir maksimal 20 karakter.',
            'kendaraan.*.supplier_id.exists' => 'Supplier yang dipilih tidak valid.',
            'kendaraan.*.jenis_kendaraan.string' => 'Jenis kendaraan harus berupa teks.',
            'kendaraan.*.jenis_kendaraan.max' => 'Jenis kendaraan maksimal 100 karakter.',
            'kendaraan.*.tujuan_id.required' => 'Tujuan kendaraan wajib dipilih.',
            'kendaraan.*.tujuan_id.exists' => 'Tujuan yang dipilih tidak valid.',
            'kendaraan.*.jumlah_kg.numeric' => 'Jumlah kg harus berupa angka.',
            'kendaraan.*.jumlah_kg.min' => 'Jumlah kg tidak boleh kurang dari 0.',
            'kendaraan.*.ongkos_angkut.numeric' => 'Ongkos angkut harus berupa angka.',
            'kendaraan.*.ongkos_angkut.min' => 'Ongkos angkut tidak boleh kurang dari 0.',
            'kendaraan.*.status.in' => 'Status kendaraan harus berupa pending, berangkat, selesai, atau batal.',
            'kendaraan.*.dp_nominal.numeric' => 'Nominal DP harus berupa angka.',
            'kendaraan.*.dp_nominal.min' => 'Nominal DP tidak boleh kurang dari 0.',
            'kendaraan.*.dp_persen.numeric' => 'Persen DP harus berupa angka.',
            'kendaraan.*.dp_persen.min' => 'Persen DP tidak boleh kurang dari 0.',
            'kendaraan.*.dp_persen.max' => 'Persen DP tidak boleh lebih dari 100.',
            'kendaraan.*.dp_tanggal.date' => 'Tanggal DP harus berupa tanggal yang valid.',
            'kendaraan.*.dp_metode.string' => 'Metode DP harus berupa teks.',
            'kendaraan.*.dp_metode.in' => 'Metode DP harus berupa transfer, tunai, atau giro.',
            'kendaraan.*.dp_keterangan.string' => 'Keterangan DP harus berupa teks.',
            'kendaraan.*.dp_keterangan.max' => 'Keterangan DP maksimal 500 karakter.',
            'kendaraan.*.penerima.array' => 'Data penerima harus berupa array.',
            'kendaraan.*.penerima.*.id.exists' => 'Penerima yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.penerima_id.exists' => 'Penerima yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.nama_penerima.string' => 'Nama penerima harus berupa teks.',
            'kendaraan.*.penerima.*.nama_penerima.max' => 'Nama penerima maksimal 255 karakter.',
            'kendaraan.*.penerima.*.tujuan_id.exists' => 'Tujuan penerima yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.no_surat_jalan.string' => 'Nomor surat jalan harus berupa teks.',
            'kendaraan.*.penerima.*.no_surat_jalan.max' => 'Nomor surat jalan maksimal 100 karakter.',
            'kendaraan.*.penerima.*.status.in' => 'Status penerima harus berupa pending, berangkat, tiba, selesai, atau batal.',
            'kendaraan.*.penerima.*.pakans.array' => 'Data pakan harus berupa array.',
            'kendaraan.*.penerima.*.pakans.*.kode_pakan_id.exists' => 'Kode pakan yang dipilih tidak valid.',
            'kendaraan.*.penerima.*.pakans.*.jumlah_kg.numeric' => 'Jumlah kg pakan harus berupa angka.',
            'kendaraan.*.penerima.*.pakans.*.jumlah_kg.min' => 'Jumlah kg pakan tidak boleh kurang dari 0.01.',
            'kendaraan.*.penerima.*.pakans.*.ongkos_oa.numeric' => 'Ongkos OA harus berupa angka.',
            'kendaraan.*.penerima.*.pakans.*.ongkos_oa.min' => 'Ongkos OA tidak boleh kurang dari 0.',
            'kendaraan.*.penerima.*.pakans.*.harga_pt_sum.numeric' => 'Harga PT Sum harus berupa angka.',
            'kendaraan.*.penerima.*.pakans.*.harga_pt_sum.min' => 'Harga PT Sum tidak boleh kurang dari 0.',
        ]);

        $po = PurchaseOrder::findOrFail($id);
        $cv = Cv::find($request->cv_id);
        if ($cv && $cv->isMelebihiBatas()) {
            return redirect()->back()
                ->with('error', 'CV yang dipilih sudah melebihi batas omzet tahunan dan tidak dapat dipilih.')
                ->withInput();
        }

        DB::beginTransaction();
        try {

            if ($po->isLocked()) {
                return redirect()->back()->with('error', 'PO sudah terkunci.');
            }

            $po->update(
                [
                    'no_po' => $request->no_po,
                    'tanggal_po' => $request->tanggal_po,
                    'catatan' => $request->catatan,
                    'cv_id' => $request->cv_id,
                ]
            );

            $submittedKendaraanIds = collect($request->kendaraan)->pluck('id')->filter()->values();
            $po->kendaraans()->whereNotIn('id', $submittedKendaraanIds)->delete();

            $savedKendaraanIds = [];

            foreach ($request->kendaraan as $kendaraanData) {
                $kendaraanId = $kendaraanData['id'] ?? null;

                $kendaraan = $kendaraanId
                    ? PoKendaraan::where('id', $kendaraanId)->where('po_id', $po->id)->firstOrFail()
                    : new PoKendaraan(['po_id' => $po->id]);

                $kendaraan->fill([
                    'no_polisi' => strtoupper(trim($kendaraanData['no_polisi'])),
                    'nama_sopir' => $kendaraanData['nama_sopir'] ?? null,
                    'no_hp' => $kendaraanData['no_hp'] ?? null,
                    'supplier_id' => $kendaraanData['supplier_id'] ?? null,
                    'jenis_kendaraan' => $kendaraanData['jenis_kendaraan'] ?? null,
                    'tujuan_id' => $kendaraanData['tujuan_id'] ?? null,
                    'ongkos_angkut' => $kendaraanData['ongkos_angkut'] ?? 0,
                    'jumlah_kg' => $kendaraanData['jumlah_kg'] ?? null,
                    'jumlah_karung' => isset($kendaraanData['jumlah_kg']) && $kendaraanData['jumlah_kg'] > 0
                        ? (int) ceil($kendaraanData['jumlah_kg'] / 50)
                        : null,
                    'status' => $kendaraanData['status'] ?? 'pending',
                    // Data DP
                    'dp_nominal' => $kendaraanData['dp_nominal'] ?? 0,
                    'dp_persen' => $kendaraanData['dp_persen'] ?? null,
                    'dp_tanggal' => $kendaraanData['dp_tanggal'] ?? null,
                    'dp_metode' => $kendaraanData['dp_metode'] ?? null,
                    'dp_keterangan' => $kendaraanData['dp_keterangan'] ?? null,
                ]);
                $kendaraan->save();
                $savedKendaraanIds[] = $kendaraan->id;
                $statusKendaraan = $kendaraanData['status'] ?? 'pending';

                // Hitung total tagihan OA untuk kendaraan ini
                $totalTagihanKendaraan = 0;
                foreach ($kendaraanData['penerima'] ?? [] as $penerimaData) {
                    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                        if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                            continue;
                        }
                        $totalTagihanKendaraan += ($pakanData['jumlah_kg'] ?? 0) * ($pakanData['ongkos_oa'] ?? 0);
                    }
                }

                // Handle DP Payment
                $dpNominal = (! empty($kendaraanData['dp_nominal']) && $kendaraanData['dp_nominal'] > 0)
                    ? $kendaraanData['dp_nominal']
                    : 0;
                $dpTanggal = $kendaraanData['dp_tanggal'] ?? null;
                $dpMetode = $kendaraanData['dp_metode'] ?? null;
                $dpKeterangan = $kendaraanData['dp_keterangan'] ?? null;

                if ($totalTagihanKendaraan > 0) {
                    $jumlahBayar = $dpNominal;
                    $tanggalBayar = $dpNominal > 0 ? ($dpTanggal ?? now()) : null;
                    $metodeBayar = $dpNominal > 0 ? ($dpMetode ?? 'transfer') : null;

                    $keterangan = 'Pembayaran OA - Kendaraan ' . $kendaraan->no_polisi . ' (PO: ' . $po->no_po . ')';
                    if ($dpNominal > 0 && $dpKeterangan) {
                        $keterangan .= ' | DP: ' . $dpKeterangan;
                    }

                    $status = 'pending';
                    if ($dpNominal > 0) {
                        $status = $dpNominal >= $totalTagihanKendaraan ? 'lunas' : 'partial';
                    }

                    // Update atau buat OaPayment
                    $oaPayment = $kendaraan->dpPayment()->first();
                    if ($oaPayment) {
                        $oaPayment->update([
                            'jumlah_tagihan' => $totalTagihanKendaraan,
                            'jumlah_bayar' => $jumlahBayar,
                            'tanggal_bayar' => $tanggalBayar,
                            'metode_bayar' => $metodeBayar,
                            'keterangan' => $keterangan,
                            'status' => $status,
                            'supplier_id' => $kendaraan->supplier_id,
                        ]);
                    } else {
                        OaPayment::create([
                            'po_kendaraan_id' => $kendaraan->id,
                            'po_penerima_id' => null,
                            'supplier_id' => $kendaraan->supplier_id,
                            'tipe_pembayaran' => 'dp_supplier',
                            'jumlah_tagihan' => $totalTagihanKendaraan,
                            'jumlah_bayar' => $jumlahBayar,
                            'tanggal_bayar' => $tanggalBayar,
                            'metode_bayar' => $metodeBayar,
                            'keterangan' => $keterangan,
                            'status' => $status,
                        ]);
                    }
                }

                $submittedPenerimaIds = collect($kendaraanData['penerima'] ?? [])->pluck('id')->filter()->values();
                $kendaraan->penerimas()->whereNotIn('id', $submittedPenerimaIds)->delete();

                foreach ($kendaraanData['penerima'] ?? [] as $penerimaData) {
                    if (empty(trim($penerimaData['nama_penerima'] ?? ''))) {
                        continue;
                    }

                    $penerimaId = $penerimaData['id'] ?? null;

                    $penerima = $penerimaId
                        ? PoPenerima::where('id', $penerimaId)->where('po_kendaraan_id', $kendaraan->id)->firstOrFail()
                        : new PoPenerima(['po_kendaraan_id' => $kendaraan->id]);

                    $penerima->fill([
                        'penerima_id' => $penerimaData['penerima_id'] ?? null,
                        'nama_penerima' => $penerimaData['nama_penerima'],
                        'tujuan_id' => $penerimaData['tujuan_id'] ?? null,
                        'no_do' => $penerimaData['no_surat_jalan'] ?? null,
                        'status' => $penerimaData['status'] === 'pending' && $statusKendaraan === 'berangkat' ? 'berangkat' : $penerimaData['status'],
                    ]);
                    $penerima->save();

                    // Sync pakans — skip pakan kosong
                    $penerima->pakans()->delete();
                    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                        if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                            continue;
                        }
                        PoPenerimaPakan::create([
                            'po_penerima_id' => $penerima->id,
                            'kode_pakan_id' => $pakanData['kode_pakan_id'],
                            'jumlah_kg' => $pakanData['jumlah_kg'],
                            'ongkos_oa' => $pakanData['ongkos_oa'] ?? 0,
                            'harga_pt_sum' => $pakanData['harga_pt_sum'] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();

            foreach ($savedKendaraanIds as $kid) {
                $k = PoKendaraan::find($kid);
                if (! $k) {
                    continue;
                }
                $sync = $this->poKendaraanIdtrackSpj->trySync($k, false, null);
                if ($sync['success'] && empty($sync['skipped'])) {
                    Log::info('Idtrack SPJ otomatis OK', ['po_kendaraan_id' => $kid]);
                } elseif (! $sync['success'] && empty($sync['skipped'])) {
                    Log::warning('Idtrack SPJ otomatis gagal', ['po_kendaraan_id' => $kid, 'message' => $sync['message']]);
                }
            }

            return redirect()->back()->with('success', 'Data PO berhasil diperbarui.');
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === '23000') {
                return redirect()->back()
                    ->with('error', 'Kode pakan duplikat pada penerima yang sama.')
                    ->withInput();
            }

            return redirect()->back()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function tujuanByCv(string $cvId)
    {
        $tujuans = Tujuan::where('cv_id', $cvId)->where('is_aktif', true)->orderBy('nama')->get(['id', 'nama', 'type']);

        return response()->json($tujuans);
    }

    // ── Lansir per penerima ───────────────────────────────────────

    public function penerimaLansirPage(string $penerimaId)
    {
        try {
            $penerima = $this->findPenerimaForLansir($penerimaId);

            // Allow access if status is 'tiba' (for adding lansir) or 'selesai' (for viewing riwayat)
            if (! in_array($penerima->status, ['tiba', 'selesai'])) {
                return redirect()->back()->with('error', 'Halaman lansir hanya dapat diakses setelah penerima berstatus Tiba atau Selesai.');
            }

            $jenisLansirDefault = 'mobil_tim';
            $isTimBongkarPage = false;

            return view('pages.purchase-order.penerima-lansir', compact('penerima', 'jenisLansirDefault', 'isTimBongkarPage'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman lansir.');
        }
    }

    public function penerimaTimBongkarPage(string $penerimaId)
    {
        try {
            $penerima = $this->findPenerimaForLansir($penerimaId);

            if (! in_array($penerima->status, ['tiba', 'selesai'])) {
                return redirect()->back()->with('error', 'Halaman tim bongkar hanya dapat diakses setelah penerima berstatus Tiba atau Selesai.');
            }

            $jenisLansirDefault = 'tim_bongkar';
            $isTimBongkarPage = true;

            return view('pages.purchase-order.penerima-lansir', compact('penerima', 'jenisLansirDefault', 'isTimBongkarPage'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman tim bongkar.');
        }
    }

    private function findPenerimaForLansir(string $penerimaId): PoPenerima
    {
        return PoPenerima::with([
            'kendaraan.po.cv',
            'kendaraan.supplier',
            'tujuan',
            'pakans.kodePakan',
            'lansirs.mobils',
            'lansirs.tims',
            'penerima', // master penerima untuk ongkos_angkut & ongkos_bongkar
        ])->findOrFail(decrypt($penerimaId));
    }

    public function penerimaStoreLansir(Request $request, string $penerimaId)
    {
        $request->validate([
            'validasi_oleh' => 'required|string|max:255',
            'tanggal_lansir' => 'required|date',
            'no_do' => 'nullable|string|max:100',
            'jenis_lansir' => 'required|in:mobil_tim,tim_bongkar',
            'mobils' => 'required_if:jenis_lansir,mobil_tim|array|min:1',
            'mobils.*.no_polisi' => 'required_if:jenis_lansir,mobil_tim|string|max:20',
            'mobils.*.nama_sopir' => 'nullable|string|max:255',
            'mobils.*.berat' => 'nullable|numeric|min:0',
            'mobils.*.jumlah_karung' => 'nullable|integer|min:0',
            'mobils.*.ongkos' => 'nullable|numeric|min:0',
            'mobils.*.keterangan' => 'nullable|string',
            'tims' => 'required|array|min:1',
            'tims.*.nama_tim' => 'nullable|string|max:255',
            'tims.*.berat' => 'nullable|numeric|min:0',
            'tims.*.jumlah_karung' => 'nullable|integer|min:0',
            'tims.*.upah' => 'nullable|numeric|min:0',
            'tims.*.keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $penerima = PoPenerima::with('kendaraan.po')->findOrFail($penerimaId);

            if ($penerima->status !== 'tiba') {
                return redirect()->back()->with('error', 'Lansir hanya bisa dilakukan setelah penerima berstatus Tiba.');
            }

            if (! $penerima->kendaraan->po->isLocked()) {
                return redirect()->back()->with('error', 'PO harus dikunci terlebih dahulu.');
            }

            $lansir = PoPenerimaLansir::create([
                'po_penerima_id' => $penerima->id,
                'validasi_oleh' => $request->validasi_oleh,
                'tanggal_lansir' => $request->tanggal_lansir,
                'no_do' => $request->no_do,
                'selesai_at' => now(),
            ]);

            foreach ($request->input('mobils', []) as $mobil) {
                if (empty(trim($mobil['no_polisi'] ?? ''))) {
                    continue;
                }
                PoLansirMobil::create([
                    'lansir_id' => $lansir->id,
                    'no_polisi' => strtoupper(trim($mobil['no_polisi'])),
                    'nama_sopir' => $mobil['nama_sopir'] ?? null,
                    'berat' => $mobil['berat'] ?? null,
                    'jumlah_karung' => (int) ($mobil['jumlah_karung'] ?? 0),
                    'ongkos' => $mobil['ongkos'] ?? null,
                    'keterangan' => $mobil['keterangan'] ?? null,
                ]);
            }

            foreach ($request->tims ?? [] as $tim) {
                if (empty(trim($tim['nama_tim'] ?? ''))) {
                    continue;
                }
                PoLansirTim::create([
                    'lansir_id' => $lansir->id,
                    'nama_tim' => trim($tim['nama_tim']),
                    'berat' => $tim['berat'] ?? null,
                    'jumlah_karung' => (int) ($tim['jumlah_karung'] ?? 0),
                    'upah' => $tim['upah'] ?? null,
                    'keterangan' => $tim['keterangan'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('po-penerima.lansir-page', encrypt($penerima->id))
                ->with('success', 'Data lansir berhasil disimpan.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function penerimaDestroyLansir(string $lansirId)
    {
        try {
            $lansir = PoPenerimaLansir::with('penerima')->findOrFail(decrypt($lansirId));
            $penerimaId = $lansir->po_penerima_id;
            $lansir->delete();
            
            return redirect()->route('po-penerima.lansir-page', encrypt($penerimaId))
                ->with('success', 'Riwayat lansir berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus riwayat lansir: ' . $e->getMessage());
        }
    }

    public function kendaraanUpdateStatus(Request $request, string $kendaraanId)
    {
        $request->validate([
            'status' => 'required|in:berangkat,selesai,batal',
        ]);

        try {
            $kendaraan = PoKendaraan::with('po', 'penerimas')->findOrFail($kendaraanId);
            $po = $kendaraan->po;

            if (! $po->isLocked()) {
                return redirect()->back()->with('error', 'PO harus dikunci terlebih dahulu.');
            }

            $allowed = PoKendaraan::VALID_TRANSITIONS[$kendaraan->status] ?? [];
            if (! in_array($request->status, $allowed)) {
                return redirect()->back()->with('error', "Transisi dari '{$kendaraan->status}' ke '{$request->status}' tidak diizinkan.");
            }

            $kendaraan->update(['status' => $request->status]);

            if ($request->status === 'berangkat') {
                $kendaraan->penerimas()->where('status', 'pending')->update(['status' => 'berangkat']);
            }

            if ($request->status === 'batal') {
                $kendaraan->penerimas()->whereIn('status', ['pending', 'berangkat'])->update(['status' => 'batal']);
            }

            $idtrackSpj = null;
            if ($request->status === 'berangkat') {
                $kendaraan->refresh()->load(['penerimas.penerima', 'penerimas.tujuan', 'po']);
                $idtrackSpj = $this->poKendaraanIdtrackSpj->trySync($kendaraan, false, null);
                if ($idtrackSpj['success'] && empty($idtrackSpj['skipped'])) {
                    Log::info('Idtrack SPJ otomatis OK', ['po_kendaraan_id' => $kendaraan->id]);
                } elseif (! $idtrackSpj['success'] && empty($idtrackSpj['skipped'])) {
                    Log::warning('Idtrack SPJ otomatis gagal', ['po_kendaraan_id' => $kendaraan->id, 'message' => $idtrackSpj['message']]);
                }
            }

            return redirect()->back()->with('success', 'Status kendaraan diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function penerimaUpdateStatus(Request $request, string $penerimaId)
    {
        $request->validate([
            'status' => 'required|in:berangkat,tiba,selesai,batal',
            'validasi_oleh' => 'required_if:status,tiba|nullable|string|max:255',
            'tanggal_tiba' => 'required_if:status,tiba|nullable|date',
            'bukti_tiba' => 'required_if:status,tiba|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'validasi_oleh.required_if' => 'Nama validator wajib diisi saat menandai tiba.',
            'tanggal_tiba.required_if' => 'Tanggal tiba wajib diisi saat menandai tiba.',
            'bukti_tiba.required_if' => 'Bukti tiba wajib diunggah saat menandai tiba.',
        ]);

        DB::beginTransaction();
        try {
            $penerima = PoPenerima::with(['kendaraan.po', 'kendaraan.penerimas', 'tujuan', 'pakans.kodePakan'])->findOrFail($penerimaId);
            $po = $penerima->kendaraan->po;

            if (! $po->isLocked()) {
                return redirect()->back()->with('error', 'PO harus dikunci terlebih dahulu.');
            }

            $allowed = PoPenerima::VALID_TRANSITIONS[$penerima->status] ?? [];
            if (! in_array($request->status, $allowed)) {
                return redirect()->back()->with('error', "Transisi dari '{$penerima->status}' ke '{$request->status}' tidak diizinkan.");
            }

            $updateData = ['status' => $request->status];

            if ($request->status === 'tiba') {
                $tibaAt = $request->date('tanggal_tiba');
                $updateData['validasi_oleh'] = $request->validasi_oleh;
                $updateData['tiba_at'] = $tibaAt->format('Y-m-d H:i:s');
                if ($request->hasFile('bukti_tiba')) {
                    $updateData['bukti_tiba'] = $request->file('bukti_tiba')->store('bukti-tiba', 'public');
                }

                // Simpan tiba_at dulu agar stok mutasi & layanan lain memakai waktu tiba manual yang sama
                $penerima->update($updateData);

                if ($penerima->tujuan && $penerima->tujuan->type === 'gudang') {
                    foreach ($penerima->pakans as $pakan) {
                        if (! $pakan->kode_pakan_id) {
                            DB::rollBack();

                            return redirect()->back()->with('error', 'Kode pakan belum diisi untuk salah satu item.');
                        }

                        $this->gudangStokService->prosesStokMasukPoPenerima($penerima, $pakan);
                    }
                }
            } else {
                $penerima->update($updateData);
            }

            // Jika selesai → cek apakah semua penerima kendaraan sudah selesai/batal
            // → otomatis update kendaraan ke selesai
            if ($request->status === 'selesai') {
                $kendaraan = $penerima->kendaraan;
                $kendaraan->load('penerimas');
                $belumSelesai = $kendaraan->penerimas
                    ->whereNotIn('status', ['selesai', 'batal'])
                    ->count();

                if ($belumSelesai === 0 && $kendaraan->penerimas->count() > 0) {
                    $kendaraan->update(['status' => 'selesai']);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Status penerima diperbarui.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function penerimaUpdateTanggalTiba(Request $request, string $penerimaId)
    {
        $request->validate([
            'tanggal_tiba' => 'required|date',
            'foto_bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2120',
        ], [
            'tanggal_tiba.required' => 'Tanggal tiba wajib diisi.',
            'tanggal_tiba.date' => 'Tanggal tiba harus berupa tanggal yang valid.',
            'foto_bukti.file' => 'Foto bukti harus berupa file.',
            'foto_bukti.mimes' => 'Foto bukti harus berupa file dengan ekstensi jpg, jpeg, png, atau pdf.',
            'foto_bukti.max' => 'Foto bukti tidak boleh lebih dari 2MB.',
        ]);
        DB::beginTransaction();
        try {
            $penerima = PoPenerima::with(['kendaraan.po'])->findOrFail($penerimaId);
            $po = $penerima->kendaraan->po;

            if (! $po->isLocked()) {
                return redirect()->back()->with('error', 'PO harus dikunci terlebih dahulu.');
            }

            if (! in_array($penerima->status, ['tiba', 'selesai'])) {
                return redirect()->back()->with('error', 'Tanggal tiba hanya bisa diubah jika penerima sudah tiba atau selesai.');
            }

            $tibaAt = $request->date('tanggal_tiba');
            if ($request->hasFile('foto_bukti')) {
                $path = $request->file('foto_bukti')->store('bukti-tiba', 'public');
                $penerima->update(['bukti_tiba' => $path]);
            }

            $penerima->update(['tiba_at' => $tibaAt->format('Y-m-d H:i:s')]);    

            DB::commit();

            return redirect()->back()->with('success', 'Tanggal tiba berhasil diperbarui.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('penerimaUpdateTanggalTiba gagal: ' . $e->getMessage(), ['penerima_id' => $penerimaId]);
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function lock(string $id)
    {
        try {
            $po = PurchaseOrder::with('kendaraans')->findOrFail($id);

            if ($po->isLocked()) {
                return response()->json(['success' => false, 'message' => 'PO sudah terkunci.']);
            }

            if (! $po->canLock()) {
                $belumSelesai = $po->kendaraans->whereNotIn('status', ['selesai', 'batal', 'berangkat'])->count();

                return response()->json([
                    'success' => false,
                    'message' => "Masih ada {$belumSelesai} kendaraan yang belum selesai atau batal.",
                ]);
            }

            $po->update(['status' => PurchaseOrder::STATUS_LOCKED]);

            return response()->json(['success' => true, 'message' => 'PO berhasil dikunci.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengunci PO.'], 500);
        }
    }

    public function unlock(string $id)
    {
        try {
            $po = PurchaseOrder::findOrFail($id);

            if (! $po->isLocked()) {
                return response()->json(['success' => false, 'message' => 'PO belum terkunci.']);
            }

            $po->update(['status' => PurchaseOrder::STATUS_DRAFT]);

            return response()->json(['success' => true, 'message' => 'PO berhasil dibuka kembali.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membuka kunci PO.'], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $po = PurchaseOrder::findOrFail($id);

            if ($po->isLocked()) {
                return redirect()->route('purchase-order.index')->with('success', 'PO Terkunci tidak bisa dihapus.');
            }

            $po->delete();

            return redirect()->route('purchase-order.index')->with('success', 'PO berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus PO: ' . $e->getMessage());
        }
    }

    // ── Legacy methods (purchase_order_items) ─────────────────────────────────

    public function itemSelesai(Request $request, string $itemId)
    {
        $request->validate([
            'validasi_oleh' => 'required|string|max:255',
            'bukti_tiba' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $item = PurchaseOrderItem::with('tujuan')->findOrFail($itemId);

            if ($item->status !== 'berangkat') {
                return response()->json(['success' => false, 'message' => 'Status item tidak valid untuk diselesaikan.']);
            }

            $po = $item->po ?? PurchaseOrder::find($item->po_id);
            if (! $po || ! $po->isLocked()) {
                return response()->json(['success' => false, 'message' => 'PO harus dikunci terlebih dahulu.']);
            }

            $path = $request->file('bukti_tiba')->store('bukti-tiba', 'public');

            DB::beginTransaction();
            $item->update([
                'status' => 'selesai',
                'tiba_at' => now(),
                'validasi_oleh' => $request->validasi_oleh,
                'bukti_tiba' => $path,
            ]);

            if ($item->tujuan && $item->tujuan->type === 'gudang') {
                if (! $item->kode_pakan_id) {
                    return response()->json(['success' => false, 'message' => 'Kode pakan belum diisi.']);
                }
                $this->gudangStokService->prosesStokMasuk($item, $item->kode_pakan_id);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Item ditandai selesai.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('itemSelesai gagal: ' . $e->getMessage(), ['item_id' => $itemId]);

            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function lansirPage(string $itemId)
    {
        try {
            $item = PurchaseOrderItem::with([
                'po.cv',
                'tujuan',
                'supplier',
                'lansirRecords.mobils',
                'lansirRecords.tims',
            ])->findOrFail(decrypt($itemId));

            return view('pages.purchase-order.lansir', compact('item'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function itemLansir(Request $request, string $itemId)
    {
        $request->validate([
            'validasi_oleh' => 'required|string|max:255',
            'mobils' => 'required|array|min:1',
            'mobils.*.no_polisi' => 'required|string|max:20',
            'mobils.*.nama_sopir' => 'nullable|string|max:255',
            'mobils.*.berat' => 'nullable|numeric|min:0',
            'mobils.*.jumlah_karung' => 'nullable|integer|min:0',
            'mobils.*.ongkos' => 'nullable|numeric|min:0',
            'tims' => 'nullable|array',
            'tims.*.nama_tim' => 'required|string|max:255',
            'tims.*.berat' => 'nullable|numeric|min:0',
            'tims.*.jumlah_karung' => 'nullable|integer|min:0',
            'tims.*.upah' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $item = PurchaseOrderItem::findOrFail($itemId);
            $po = PurchaseOrder::find($item->po_id);

            if (! $po || ! $po->isLocked()) {
                return redirect()->back()->with('error', 'PO harus dikunci terlebih dahulu.');
            }

            if (! in_array($item->status, ['berangkat', 'lansir'])) {
                return redirect()->back()->with('error', 'Status item tidak valid untuk lansir.');
            }

            $item->update([
                'status' => 'lansir',
                'tiba_at' => $item->tiba_at ?? now(),
                'validasi_oleh' => $request->validasi_oleh,
            ]);

            $lansir = PoItemLansir::create([
                'po_item_id' => $item->id,
                'validasi_oleh' => $request->validasi_oleh,
                'selesai_at' => now(),
            ]);

            foreach ($request->mobils as $mobil) {
                if (empty(trim($mobil['no_polisi'] ?? ''))) {
                    continue;
                }
                PoLansirMobil::create([
                    'lansir_id' => $lansir->id,
                    'no_polisi' => strtoupper(trim($mobil['no_polisi'])),
                    'nama_sopir' => $mobil['nama_sopir'] ?? null,
                    'berat' => $mobil['berat'] ?? null,
                    'jumlah_karung' => (int) ($mobil['jumlah_karung'] ?? 0),
                    'ongkos' => $mobil['ongkos'] ?? null,
                ]);
            }

            foreach ($request->tims ?? [] as $tim) {
                if (empty(trim($tim['nama_tim'] ?? ''))) {
                    continue;
                }
                PoLansirTim::create([
                    'lansir_id' => $lansir->id,
                    'nama_tim' => trim($tim['nama_tim']),
                    'berat' => $tim['berat'] ?? null,
                    'jumlah_karung' => (int) ($tim['jumlah_karung'] ?? 0),
                    'upah' => $tim['upah'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('po-item.lansir-page', encrypt($item->id))
                ->with('success', 'Data lansir berhasil disimpan.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function lansirSelesai(Request $request, string $itemId)
    {
        $request->validate(['bukti_tiba' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);

        try {
            $item = PurchaseOrderItem::findOrFail($itemId);

            if ($item->status !== 'lansir') {
                return redirect()->back()->with('error', 'Item bukan dalam status lansir.');
            }

            if ($item->selesai_lansir_at) {
                return redirect()->back()->with('error', 'Lansir sudah ditandai selesai sebelumnya.');
            }

            $path = $request->file('bukti_tiba')->store('bukti-tiba', 'public');
            $item->update(['status' => 'selesai', 'bukti_tiba' => $path, 'selesai_lansir_at' => now()]);

            return redirect()->route('po-item.lansir-page', encrypt($item->id))
                ->with('success', 'Lansir ditandai selesai.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function lansirDetail(string $itemId)
    {
        $item = PurchaseOrderItem::with('lansirRecords')->findOrFail($itemId);

        return response()->json(['item' => $item, 'lansirs' => $item->lansirRecords]);
    }
}
