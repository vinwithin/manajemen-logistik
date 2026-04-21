<?php

namespace App\Http\Controllers;

use App\Models\GudangStok;
use App\Models\KodePakan;
use App\Models\Tujuan;
use App\Services\Datatables\GudangMutasiDatatableService;
use App\Services\Datatables\GudangStokDatatableService;
use Illuminate\Http\Request;
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
                ->addIndexColumn()
                ->make(true);
        }

        return view('pages.gudang.stok.show', compact('gudang'));
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
