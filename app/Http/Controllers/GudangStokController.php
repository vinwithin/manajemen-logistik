<?php

namespace App\Http\Controllers;

use App\Exports\KartuStockMutasiExport;
use App\Exports\StokKeluarExport;
use App\Models\GudangStok;
use App\Models\KodePakan;
use App\Models\Tujuan;
use App\Services\Datatables\GudangMutasiDatatableService;
use App\Services\Datatables\GudangStokDatatableService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class GudangStokController extends Controller
{
    protected $gudangStokDatatableService;

    protected $gudangMutasiDatatableService;

    public function __construct(
        GudangStokDatatableService $gudangStokDatatableService,
        GudangMutasiDatatableService $gudangMutasiDatatableService
    ) {
        // $this->middleware('permission:gudang-stok.view');
        $this->gudangStokDatatableService = $gudangStokDatatableService;
        $this->gudangMutasiDatatableService = $gudangMutasiDatatableService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Tujuan::where('type', 'gudang')
                ->where('is_aktif', true)
                ->withCount(['stoks as total_jenis_pakan'])
                ->withSum(['stoks as total_kg'], 'stok_kg')
                ->withSum(['stoks as total_karung'], 'stok_karung')
                ->get();

            return DataTables::of($query)
                ->addColumn('total_kg_fmt', fn ($q) => number_format($q->total_kg ?? 0, 0, ',', '.').' kg')
                ->addColumn('total_karung_fmt', fn ($q) => number_format($q->total_karung ?? 0, 0, ',', '.').' karung')
                ->addColumn('action', fn ($q) => '<a href="'.route('gudang.stok.show', $q->id).'" class="btn btn-xs btn-outline-primary py-0 px-1 small"><i class="fa fa-archive"></i> Lihat Stok</a>')
                ->addIndexColumn()
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.gudang.stok.index');
    }

    public function show(Request $request, int $id)
    {
        $gudang = Tujuan::where('type', 'gudang')->findOrFail($id);

        if ($request->ajax()) {
            $query = GudangStok::with('kodePakan')
                ->where('tujuan_id', $id);

            return DataTables::of($query)
                ->addColumn('kode_pakan', fn ($q) => $q->kodePakan?->kode ?? '-')
                ->addColumn('nama_pakan', fn ($q) => $q->kodePakan?->nama ?? '-')
                ->addColumn('stok_kg_fmt', fn ($q) => number_format($q->stok_kg, 0, ',', '.').' kg')
                ->addColumn('stok_karung_fmt', fn ($q) => number_format($q->stok_karung, 0, ',', '.').' karung')
                ->addColumn('action', function ($q) use ($id) {
                    $url = route('gudang.mutasi.index', [
                        'tujuan_id' => $id,
                        'kode_pakan_id' => $q->kode_pakan_id,
                    ]);

                    return '<a href="'.$url.'" class="btn btn-xs btn-outline-info py-0 px-2 small">
                                <i class="fa fa-history"></i> Mutasi
                            </a>';
                })
                ->addIndexColumn()
                ->rawColumns(['action'])
                ->make(true);
        }

        $kodePakans = KodePakan::orderBy('kode')->get();

        return view('pages.gudang.stok.show', compact('gudang', 'kodePakans'));
    }

    public function mutasi(Request $request)
    {
        if ($request->ajax()) {
            return $this->gudangMutasiDatatableService->getData($request);
        }

        $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->get();
        $kodePakans = KodePakan::orderBy('kode')->get();

        return view('pages.gudang.mutasi.index', compact('gudangs', 'kodePakans'));
    }

    public function mutasiExport(Request $request)
    {
        $tipeFilter = null;
        if ($request->filled('tipe')) {
            $t = strtolower(trim((string) $request->input('tipe')));
            if (in_array($t, ['masuk', 'keluar'], true)) {
                $tipeFilter = $t;
            }
        }

        $filename = 'kartu-stock';
        if ($request->tujuan_id) {
            $gudang = Tujuan::find($request->tujuan_id);
            $filename .= '-'.str_replace(' ', '-', strtolower($gudang?->nama ?? 'gudang'));
        }
        if ($request->kode_pakan_id) {
            $pakan = KodePakan::find($request->kode_pakan_id);
            $filename .= '-'.strtolower($pakan?->kode ?? 'pakan');
        }
        if ($tipeFilter) {
            $filename .= '-'.$tipeFilter;
        }
        if ($request->dari_tanggal) {
            $filename .= '-'.$request->dari_tanggal;
        }
        $filename .= '.xlsx';

        return Excel::download(
            new KartuStockMutasiExport(
                $request->tujuan_id ? (int) $request->tujuan_id : null,
                $request->dari_tanggal ?: null,
                $request->sampai_tanggal ?: null,
                $request->kode_pakan_id ? (int) $request->kode_pakan_id : null,
                $tipeFilter,
            ),
            $filename
        );
    }

    public function stokKeluarExport(Request $request)
    {
        $tujuanId = $request->tujuan_id ? (int) $request->tujuan_id : null;
        $dari = $request->dari_tanggal ?: null;
        $sampai = $request->sampai_tanggal ?: null;

        $gudang = $tujuanId ? Tujuan::find($tujuanId) : null;
        $filename = 'stok-keluar';
        if ($gudang) {
            $filename .= '-'.str_replace(' ', '-', strtolower($gudang->nama));
        }
        if ($dari) {
            $filename .= '-'.$dari;
        }
        $filename .= '.xlsx';

        return Excel::download(
            new StokKeluarExport($tujuanId, $dari, $sampai),
            $filename
        );
    }

    public function saldo(Request $request)
    {
        $request->validate([
            'tujuan_id' => 'required|integer',
            'kode_pakan_id' => 'required|integer',
        ]);

        $stok = GudangStok::where('tujuan_id', $request->tujuan_id)
            ->where('kode_pakan_id', $request->kode_pakan_id)
            ->first();

        return response()->json([
            'stok_kg' => $stok ? $stok->stok_kg : 0,
            'stok_karung' => $stok ? $stok->stok_karung : 0,
        ]);
    }
}
