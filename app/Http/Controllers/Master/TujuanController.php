<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tujuan;
use App\Services\Datatables\TujuanService;
use Exception;
use Illuminate\Http\Request;

class TujuanController extends Controller
{
    protected $tujuanService;

    public function __construct(TujuanService $tujuanService)
    {
        $this->tujuanService = $tujuanService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Tujuan::select(['id', 'nama', 'type', 'is_aktif']);

            return $this->tujuanService->getData($query);
        }

        return view('pages.tujuan.index', [
            'types' => Tujuan::TYPES,
        ]);
    }

    public function create()
    {
        return view('pages.tujuan.create', [
            'types' => Tujuan::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(Tujuan::TYPES)),
        ]);

        try {
            Tujuan::create([
                'nama' => $request->nama,
                'type' => $request->type,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
            ]);

            return redirect()->route('tujuan.index')->with('success', 'Tujuan pengiriman berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function show(string $id) {}

    public function edit(string $id)
    {
        try {
            $id = decrypt($id);
            $data = Tujuan::findOrFail($id);

            return view('pages.tujuan.edit', [
                'data' => $data,
                'types' => Tujuan::TYPES,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(Tujuan::TYPES)),
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'geofence_radius' => 'nullable|integer|min:50|max:5000',
        ]);

        try {
            $tujuan = Tujuan::findOrFail($id);
            $tujuan->update([
                'nama' => $request->nama,
                'type' => $request->type,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'geofence_radius' => $request->geofence_radius ?? 500,
                'idtrack_marker_id' => $request->idtrack_marker_id ?: null,
            ]);

            return redirect()->route('tujuan.index')->with('success', 'Tujuan pengiriman berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->route('tujuan.index')->with('error', 'Tujuan pengiriman gagal diperbarui.');

        }
    }

    public function destroy(string $id)
    {
        try {
            Tujuan::findOrFail($id)->delete();

            return redirect()->back()->with('success', 'Berhasil menghapus data');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data');

        }
    }

    public function quickStore(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'type' => 'required|in:direct,gudang,co_farm,rent_farm',
            'cv_id' => 'nullable|exists:cv,id',
        ]);

        try {
            $tujuan = Tujuan::create([
                'nama' => $request->nama,
                'type' => $request->type,
                'cv_id' => $request->cv_id,
                'is_aktif' => true,
            ]);

            return response()->json([
                'success' => true,
                'tujuan' => [
                    'id' => $tujuan->id,
                    'nama' => $tujuan->nama,
                    'type' => $tujuan->type,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
