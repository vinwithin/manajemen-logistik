<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\GudangLansir;
use App\Models\GudangLansirKendaraan;
use App\Models\GudangStok;
use App\Models\KodePakan;
use App\Models\Tujuan;
use App\Services\Datatables\GudangLansirDatatableService;
use App\Services\GudangStokService;
use Exception;
use Illuminate\Http\Request;

class GudangLansirController extends Controller
{
    protected $gudangLansirDatatableService;
    protected $gudangStokService;

    public function __construct(
        GudangLansirDatatableService $gudangLansirDatatableService,
        GudangStokService $gudangStokService
    ) {
        $this->gudangLansirDatatableService = $gudangLansirDatatableService;
        $this->gudangStokService            = $gudangStokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->gudangLansirDatatableService->getData($request);
        }

        $gudangs    = Tujuan::where('type', 'gudang')->where('is_aktif', true)->get();
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
        
        return view('pages.gudang.lansir.create', compact('gudangs', 'tujuans', 'kodePakans', 'gudangId', 'stokList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:tujuan,id',
            'no_polisi' => 'required|string|max:20',
            'nama_sopir' => 'nullable|string|max:255',
            'no_surat_jalan' => 'nullable|string|max:100',
            'tanggal_lansir' => 'required|date',
            'catatan' => 'nullable|string',
            'penerimas' => 'required|array|min:1',
            'penerimas.*.nama_penerima' => 'required|string|max:255',
            'penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'penerimas.*.pakans' => 'required|array|min:1',
            'penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
            'penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
            'penerimas.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'penerimas.*.tims' => 'nullable|array',
            'penerimas.*.tims.*.nama_tim' => 'required|string|max:255',
            'penerimas.*.tims.*.jumlah_kg' => 'required|numeric|min:0.01',
            'penerimas.*.tims.*.upah_per_kg' => 'nullable|numeric|min:0',
        ]);

        try {
            $kendaraan = $this->gudangStokService->prosesLansirGudangNested($request->all());
            
            return redirect()->route('gudang.lansir.show', encrypt($kendaraan->id))
                ->with('success', 'Lansir gudang berhasil disimpan dan stok telah dikurangi.');
        } catch (InsufficientStockException $e) {
            return redirect()->back()
                ->with('error', 'Stok tidak mencukupi. Tersedia: ' . $e->getMessage() . ' kg')
                ->withInput();
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(string $id)
    {
        try {
            $id = decrypt($id);
            $kendaraan = GudangLansirKendaraan::with([
                'gudang',
                'penerimas.tujuan',
                'penerimas.pakans.kodePakan',
                'penerimas.tims',
                'creator'
            ])->findOrFail($id);

            // Kode pakan unik untuk kolom pivot
            $kodePakanList = $kendaraan->penerimas
                ->flatMap(fn ($p) => $p->pakans)
                ->map(fn ($pk) => $pk->kodePakan)
                ->filter()
                ->unique('id')
                ->sortBy('kode')
                ->values();

            return view('pages.gudang.lansir.show', compact('kendaraan', 'kodePakanList'));

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Data lansir tidak ditemukan.');
        }
    }

    public function penerimaUpdateStatus(Request $request, string $id)
    {
        try {
            $id = decrypt($id);
            $penerima = \App\Models\GudangLansirPenerima::findOrFail($id);

            $request->validate([
                'status' => 'required|in:tiba,selesai',
                'bukti_tiba' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($request->status === 'tiba') {
                // Upload bukti tiba
                $buktiPath = null;
                if ($request->hasFile('bukti_tiba')) {
                    $file = $request->file('bukti_tiba');
                    $filename = 'bukti_tiba_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
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

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update status: ' . $e->getMessage());
        }
    }
}
