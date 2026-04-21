<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\KodePakan;
use App\Services\Datatables\KodePakanService;
use Exception;
use Illuminate\Http\Request;

class KodePakanController extends Controller
{
    protected $service;

    public function __construct(KodePakanService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->service->getData(KodePakan::select(['id', 'nama', 'kode']));
        }
        return view('pages.kode-pakan.index');
    }

    public function create()
    {
        return view('pages.kode-pakan.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:50|unique:kode_pakan,kode',
        ]);

        try {
            KodePakan::create(['nama' => $request->nama, 'kode' => strtoupper($request->kode)]);
            return redirect()->route('pakan.index')->with('success', 'Kode pakan berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan.')->withInput();
        }
    }

    public function show(string $id) {}

    public function edit(string $id)
    {
        try {
            $data = KodePakan::findOrFail(decrypt($id));
            return view('pages.kode-pakan.edit', compact('data'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:50|unique:kode_pakan,kode,' . $id,
        ]);

        try {
            KodePakan::findOrFail($id)->update(['nama' => $request->nama, 'kode' => strtoupper($request->kode)]);
            return redirect()->route('pakan.index')->with('success', 'Kode pakan berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui.')->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            KodePakan::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Kode pakan berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }
}
