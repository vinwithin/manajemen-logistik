<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Penerima;
use App\Models\Tujuan;
use App\Services\Datatables\PenerimaService;
use Exception;
use Illuminate\Http\Request;

class PenerimaController extends Controller
{
    protected $penerimaService;

    public function __construct(PenerimaService $penerimaService)
    {
        $this->penerimaService = $penerimaService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Penerima::with('tujuan')->select('penerima.*');
            return $this->penerimaService->getData($query);
        }
        return view('pages.penerima.index');
    }

    public function create()
    {
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
        return view('pages.penerima.create', compact('tujuans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tujuan_id' => 'required|exists:tujuan,id',
            'ongkos_angkut' => 'required|numeric|min:0',
            'ongkos_bongkar' => 'nullable|numeric|min:0',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'is_aktif' => 'boolean',
        ]);

        try {
            Penerima::create($request->all());
            return redirect()->route('penerima.index')->with('success', 'Penerima berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(string $id) {}

    public function edit(string $id)
    {
        try {
            $data = Penerima::findOrFail(decrypt($id));
            $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
            return view('pages.penerima.edit', compact('data', 'tujuans'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama'            => 'required|string|max:255',
            'tujuan_id'       => 'required|exists:tujuan,id',
            'ongkos_angkut'   => 'required|numeric|min:0',
            'ongkos_bongkar'  => 'nullable|numeric|min:0',
            'alamat'          => 'nullable|string',
            'telepon'         => 'nullable|string|max:20',
            'is_aktif'        => 'boolean',
            'lat'             => 'nullable|numeric|between:-90,90',
            'lng'             => 'nullable|numeric|between:-180,180',
            'geofence_radius' => 'nullable|integer|min:50|max:5000',
        ]);

        try {
            $penerima = Penerima::findOrFail($id);
            $penerima->update($request->only([
                'nama', 'tujuan_id', 'ongkos_angkut', 'ongkos_bongkar',
                'alamat', 'telepon', 'is_aktif', 'lat', 'lng', 'geofence_radius',
            ]) + ['is_aktif' => $request->has('is_aktif') ? 1 : 0]);
            return redirect()->route('penerima.index')->with('success', 'Penerima berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            Penerima::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Penerima berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }

    /**
     * Get ongkos angkut untuk penerima tertentu
     */
    public function getOngkosAngkut(Request $request)
    {
        try {
            $penerimaId = $request->penerima_id;

            if (!$penerimaId) {
                return response()->json(['success' => false, 'message' => 'Penerima harus diisi'], 400);
            }

            $penerima = Penerima::findOrFail($penerimaId);

            return response()->json([
                'success' => true,
                'ongkos_angkut' => $penerima->ongkos_angkut,
                'tujuan_id' => $penerima->tujuan_id,
                'tujuan_nama' => $penerima->tujuan->nama ?? '',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get list penerima berdasarkan tujuan
     */
    public function getByTujuan(Request $request)
    {
        try {
            $tujuanId = $request->tujuan_id;

            if (!$tujuanId) {
                return response()->json(['success' => false, 'message' => 'Tujuan harus diisi'], 400);
            }

            $penerimas = Penerima::where('tujuan_id', $tujuanId)
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'ongkos_angkut']);

            return response()->json([
                'success' => true,
                'penerimas' => $penerimas,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
