<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exports\RekapLansirGudangExport;
use App\Models\GudangLansirHeader;
use App\Models\GudangLansirPenerima;
use App\Models\GudangStok;
use App\Models\KodePakan;
use App\Models\Penerima;
use App\Models\PoPenerima;
use App\Models\PoPeriodeDokumen;
use App\Models\Tujuan;
use App\Services\Datatables\GudangLansirDatatableService;
use App\Services\GudangStokService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class GudangLansirController extends Controller
{
    protected $gudangLansirDatatableService;

    protected $gudangStokService;

    public function __construct(
        GudangLansirDatatableService $gudangLansirDatatableService,
        GudangStokService $gudangStokService
    ) {
        $this->gudangLansirDatatableService = $gudangLansirDatatableService;
        $this->gudangStokService = $gudangStokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->gudangLansirDatatableService->getData($request);
        }

        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->get();
        $kodePakans = KodePakan::orderBy('kode')->get();

        return view('pages.gudang.lansir.index', compact('gudangs', 'kodePakans'));
    }

    public function create(Request $request)
    {
        $gudangId = $request->gudang_id ?? session('active_gudang');
        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
        $kodePakans = KodePakan::orderBy('kode')->get();

        // Get stok tersedia per gudang
        $stokList = collect();
        if ($gudangId) {
            $stokList = GudangStok::where('tujuan_id', $gudangId)
                ->with('kodePakan')
                ->where('stok_kg', '>', 0)
                ->get();
        }

        $poPenerimaList = PoPenerima::with(['kendaraan.po', 'pakans.kodePakan', 'tujuan'])
            ->where('status', 'tiba')
            ->whereHas('tujuan', fn ($q) => $q->where('type', 'gudang'))
            ->when($gudangId, fn ($q) => $q->where('tujuan_id', $gudangId))
            ->whereDoesntHave('gudangLansirs') // Belum dilansir
            ->orderBy('tiba_at', 'desc')
            ->get();

        $penerimaList = Penerima::with('tujuan')
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'tujuan_id' => $p->tujuan_id,
                'tujuan_nama' => $p->tujuan?->nama ?? '',
                'ongkos_angkut' => (float) $p->ongkos_angkut,
                'ongkos_bongkar' => (float) $p->ongkos_bongkar,
            ]);

        $cvList = \App\Models\Cv::where('is_aktif', true)->orderBy('nama_cv')->get();

        return view('pages.gudang.lansir.create', compact('gudangs', 'tujuans', 'kodePakans', 'gudangId', 'stokList', 'poPenerimaList', 'penerimaList', 'cvList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'gudang_id'      => 'required|exists:tujuan,id',
            'cv_id'          => 'required|exists:cv,id',
            'tanggal_lansir' => 'required|date',
            'catatan'        => 'nullable|string',
            'kendaraans' => 'required|array|min:1',
            'kendaraans.*.no_polisi' => 'required|string|max:20',
            'kendaraans.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraans.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraans.*.penerimas' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.nama_penerima' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraans.*.penerimas.*.pakans' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.pakans.*.keterangan' => 'nullable|string|max:255',
            'kendaraans.*.penerimas.*.tims' => 'nullable|array',
            'kendaraans.*.penerimas.*.tims.*.nama_tim' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.tims.*.jumlah_kg' => 'required|numeric|min:0.01',
            'kendaraans.*.penerimas.*.tims.*.upah_per_kg' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.tims.*.keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $header = $this->gudangStokService->prosesLansirGudangNested($request->all());

            return redirect()->route('gudang.lansir.show', encrypt($header->id))
                ->with('success', 'Lansir gudang berhasil disimpan. No Lansir: '.$header->no_lansir);
        } catch (InsufficientStockException $e) {
            return redirect()->back()
                ->with('error', 'Stok tidak mencukupi. Tersedia: '.$e->getMessage().' kg')
                ->withInput();
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(string $id)
    {
        try {
            $id = decrypt($id);
            $header = GudangLansirHeader::with([
                'gudang',
                'cv',
                'kendaraans.penerimas.tujuan',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tims',
                'kendaraans.penerimas.validator',
                'creator',
            ])->findOrFail($id);

            // Kode pakan unik untuk kolom pivot (dari semua kendaraan)
            $kodePakanList = $header->kendaraans
                ->flatMap(fn ($k) => $k->penerimas)
                ->flatMap(fn ($p) => $p->pakans)
                ->map(fn ($pk) => $pk->kodePakan)
                ->filter()
                ->unique('id')
                ->sortBy('kode')
                ->values();

            return view('pages.gudang.lansir.show', compact('header', 'kodePakanList'));

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Data lansir tidak ditemukan.');
        }
    }

    public function penerimaUpdateStatus(Request $request, string $id)
    {
        try {
            $id = decrypt($id);
            $penerima = GudangLansirPenerima::findOrFail($id);

            $request->validate([
                'status' => 'required|in:tiba,selesai',
                'bukti_tiba' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($request->status === 'tiba') {
                // Upload bukti tiba
                $buktiPath = null;
                if ($request->hasFile('bukti_tiba')) {
                    $file = $request->file('bukti_tiba');
                    $filename = 'bukti_tiba_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $buktiPath = $file->storeAs('bukti_tiba', $filename, 'public');
                }

                $penerima->update([
                    'status' => 'tiba',
                    'bukti_tiba' => $buktiPath,
                    'tiba_at' => now(),
                    'validasi_oleh' => auth()->id(),
                ]);

                return redirect()->back()->with('success', 'Status penerima berhasil diubah menjadi Tiba.');
            }

            if ($request->status === 'selesai') {
                if ($penerima->status !== 'tiba') {
                    return redirect()->back()->with('error', 'Penerima harus berstatus Tiba terlebih dahulu.');
                }

                $penerima->update(['status' => 'selesai']);

                return redirect()->back()->with('success', 'Status penerima berhasil diubah menjadi Selesai.');
            }

            return redirect()->back()->with('error', 'Status tidak valid.');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal update status: '.$e->getMessage());
        }
    }

    public function getPoPenerimaData(string $id)
    {
        try {
            $poPenerima = PoPenerima::with(['pakans.kodePakan', 'tujuan', 'kendaraan.po'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'po_no' => $poPenerima->kendaraan->po->no_po,
                    'nama_penerima' => $poPenerima->nama_penerima,
                    'tujuan_id' => $poPenerima->tujuan_id,
                    'tujuan_nama' => $poPenerima->tujuan->nama,
                    'tiba_at' => $poPenerima->tiba_at?->format('d/m/Y H:i'),
                    'pakans' => $poPenerima->pakans->map(fn ($p) => [
                        'kode_pakan_id' => $p->kode_pakan_id,
                        'kode' => $p->kodePakan->kode,
                        'nama' => $p->kodePakan->nama,
                        'jumlah_kg' => $p->jumlah_kg,
                        'jumlah_karung' => $p->jumlah_karung,
                        'ongkos_oa' => $p->ongkos_oa,
                        'harga_pt_sum' => $p->harga_pt_sum ?? 0,
                    ]),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan: '.$e->getMessage(),
            ], 404);
        }
    }

    public function exportRekap(Request $request)
    {
        $from = $request->dari_tanggal ?: null;
        $to = $request->sampai_tanggal ?: null;
        $gudangId = $request->gudang_id ? (int) $request->gudang_id : null;

        $filename = 'rekap-lansir-gudang';
        if ($from) {
            $filename .= '-'.$from;
        }
        if ($to) {
            $filename .= '-sd-'.$to;
        }
        $filename .= '.xlsx';

        return Excel::download(
            new RekapLansirGudangExport($from, $to, $gudangId),
            $filename
        );
    }

    public function exportPdfPtSumConfirm(Request $request)
    {
        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
        $cvList  = \App\Models\Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $suppliers = \App\Models\Supplier::orderBy('nama')->get();
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
        
        $from     = $request->from;
        $to       = $request->to;
        $gudangId = $request->gudang_id;
        $cvId     = $request->cv_id;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;
        $lansirCount    = null;
        $noSuratSuggest = null;
        $dokumen        = null;

        if ($from && $to) {
            $query = GudangLansirHeader::whereDate('tanggal_lansir', '>=', $from)
                ->whereDate('tanggal_lansir', '<=', $to)
                ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

            // Filter supplier: cek dari poPenerima -> kendaraan -> po -> supplier
            if ($supplierId) {
                $query->whereHas('kendaraans.penerimas.poPenerima.kendaraan', fn ($q) => 
                    $q->where('supplier_id', $supplierId)
                );
            }

            // Filter tujuan: cek dari penerimas
            if ($tujuanId) {
                $query->whereHas('kendaraans.penerimas', fn ($q) => 
                    $q->where('tujuan_id', $tujuanId)
                );
            }

            $lansirCount = $query->count();

            if ($cvId) {
                $cv = \App\Models\Cv::find($cvId);

                $dokumen = PoPeriodeDokumen::where('cv_id', $cvId)
                    ->where('dari', $from)
                    ->where('sampai', $to)
                    ->where('tipe', 'gudang_ptsum')
                    ->first();

                if (! $dokumen && $cv) {
                    $generated = PoPeriodeDokumen::generateNoSurat($cv, 'gudang_ptsum', $from);
                    $noSuratSuggest = $generated['no_surat'];
                }
            } else {
                // Tanpa CV: generate preview sederhana
                $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                $bulan  = $bulanRomawi[(int) date('n', strtotime($from)) - 1];
                $tahun  = date('Y', strtotime($from));
                $urutan = PoPeriodeDokumen::where('tipe', 'gudang_ptsum')
                    ->whereYear('dari', $tahun)->max('urutan') ?? 0;
                $noSuratSuggest = ($urutan + 1) . '-GL/' . $bulan . '/' . $tahun;
            }
        }

        return view('pages.gudang.lansir.export-ptsum-confirm', compact(
            'gudangs', 'cvList', 'suppliers', 'tujuans', 'from', 'to', 'gudangId', 'cvId', 
            'supplierId', 'tujuanId', 'lansirCount', 'noSuratSuggest', 'dokumen'
        ));
    }

    public function exportPdfPtSum(Request $request)
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);

        $from = $request->from;
        $to = $request->to;
        $gudangId = $request->gudang_id;
        $cvId = $request->cv_id;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;
        $buatNoSurat = $request->boolean('buat_no_surat'); // checkbox
        $noSurat = null;

        // Simpan / ambil nomor surat jika checkbox dicentang
        if ($buatNoSurat && $from && $to) {
            $cv = $cvId ? \App\Models\Cv::find($cvId) : null;

            if ($cv) {
                // Gunakan database transaction dan locking untuk menghindari race condition
                $dokumen = \DB::transaction(function () use ($cvId, $from, $to, $cv, $request) {
                    // Generate nomor surat baru (selalu increment)
                    $generated = PoPeriodeDokumen::generateNoSurat($cv, 'gudang_ptsum', $from);

                    // Buat dokumen baru
                    return PoPeriodeDokumen::create([
                        'cv_id'      => $cvId,
                        'dari'       => $from,
                        'sampai'     => $to,
                        'tipe'       => 'gudang_ptsum',
                        'urutan'     => $generated['urutan'],
                        'no_surat'   => $generated['no_surat'],
                        'catatan'    => $request->catatan,
                        'created_by' => auth()->id(),
                    ]);
                });

                $noSurat = $dokumen->no_surat;
            }
        }

        $query = GudangLansirHeader::with([
            'gudang',
            'cv',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.poPenerima.kendaraan.po.cv',
        ])->whereDate('tanggal_lansir', '>=', $from)
            ->whereDate('tanggal_lansir', '<=', $to)
            ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
            ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

        // Filter supplier
        if ($supplierId) {
            $query->whereHas('kendaraans.penerimas.poPenerima.kendaraan', fn ($q) => 
                $q->where('supplier_id', $supplierId)
            );
        }

        // Filter tujuan
        if ($tujuanId) {
            $query->whereHas('kendaraans.penerimas', fn ($q) => 
                $q->where('tujuan_id', $tujuanId)
            );
        }

        $headers = $query->orderBy('tanggal_lansir')->get();

        $kodePakanList = KodePakan::orderBy('kode')->get();

        $pdf = Pdf::loadView('pdf.gudang-lansir-ptsum',
            compact('headers', 'kodePakanList', 'from', 'to', 'noSurat'))
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)->setOption('margin-right', 10);

        return $pdf->stream('lansir-gudang-ptsum-'.now()->format('Ymd').'.pdf');
    }

    public function exportPdfSupplierConfirm(Request $request)
    {
        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->orderBy('nama')->get();
        $cvList  = \App\Models\Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $suppliers = \App\Models\Supplier::orderBy('nama')->get();
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
        
        $from     = $request->from;
        $to       = $request->to;
        $gudangId = $request->gudang_id;
        $cvId     = $request->cv_id;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;
        $lansirCount = null;

        if ($from && $to) {
            $query = GudangLansirHeader::whereDate('tanggal_lansir', '>=', $from)
                ->whereDate('tanggal_lansir', '<=', $to)
                ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
                ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

            // Filter supplier
            if ($supplierId) {
                $query->whereHas('kendaraans.penerimas.poPenerima.kendaraan', fn ($q) => 
                    $q->where('supplier_id', $supplierId)
                );
            }

            // Filter tujuan
            if ($tujuanId) {
                $query->whereHas('kendaraans.penerimas', fn ($q) => 
                    $q->where('tujuan_id', $tujuanId)
                );
            }

            $lansirCount = $query->count();
        }

        return view('pages.gudang.lansir.export-supplier-confirm', compact(
            'gudangs', 'cvList', 'suppliers', 'tujuans', 'from', 'to', 'gudangId', 'cvId', 
            'supplierId', 'tujuanId', 'lansirCount'
        ));
    }

    public function exportPdfSupplier(Request $request)
    {
        $request->validate(['from' => 'required|date', 'to' => 'required|date']);
        
        $from = $request->from;
        $to = $request->to;
        $gudangId = $request->gudang_id;
        $cvId = $request->cv_id;
        $supplierId = $request->supplier_id;
        $tujuanId = $request->tujuan_id;

        $query = GudangLansirHeader::with([
            'gudang',
            'cv',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.poPenerima.kendaraan.po.cv',
        ])->whereDate('tanggal_lansir', '>=', $from)
            ->whereDate('tanggal_lansir', '<=', $to)
            ->when($gudangId, fn ($q) => $q->where('gudang_id', $gudangId))
            ->when($cvId, fn ($q) => $q->where('cv_id', $cvId));

        // Filter supplier
        if ($supplierId) {
            $query->whereHas('kendaraans.penerimas.poPenerima.kendaraan', fn ($q) => 
                $q->where('supplier_id', $supplierId)
            );
        }

        // Filter tujuan
        if ($tujuanId) {
            $query->whereHas('kendaraans.penerimas', fn ($q) => 
                $q->where('tujuan_id', $tujuanId)
            );
        }

        $headers = $query->orderBy('tanggal_lansir')->get();

        $kodePakanList = $headers->flatMap(fn ($h) => $h->kendaraans)
            ->flatMap(fn ($k) => $k->penerimas)
            ->flatMap(fn ($p) => $p->pakans)
            ->map(fn ($pk) => $pk->kodePakan)->filter()->unique('id')->sortBy('kode')->values();

        $pdf = Pdf::loadView('pdf.gudang-lansir-supplier',
            compact('headers', 'kodePakanList', 'from', 'to'))
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)->setOption('margin-right', 10);

        return $pdf->stream('lansir-gudang-supplier-'.now()->format('Ymd').'.pdf');
    }
}
