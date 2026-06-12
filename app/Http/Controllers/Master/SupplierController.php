<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Tujuan;
use App\Services\Datatables\SupplierService;
use Exception;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Supplier::select(['id', 'nama', 'initial']);

            return $this->supplierService->getData($query);
        }

        return view('pages.supplier.index');
    }

    public function create()
    {
        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();

        return view('pages.supplier.create', compact('tujuans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'initial' => 'required|string|max:20|unique:suppliers,initial',
            'tujuans' => 'nullable|array',
            'tujuans.*.tujuan_id' => 'required|exists:tujuan,id',
            'tujuans.*.jenis_kendaraan' => 'nullable|string|max:100',
            'tujuans.*.ongkos_angkut' => 'required|numeric|min:0',
            'tujuans.*.harga_pt_sum' => 'nullable|numeric|min:0',
        ]);

        try {
            $supplier = Supplier::create($request->only('nama', 'initial'));

            // Insert tujuans dengan ongkos_angkut dan jenis_kendaraan
            if ($request->has('tujuans')) {
                foreach ($request->tujuans as $tujuan) {
                    $supplier->tujuans()->attach($tujuan['tujuan_id'], [
                        'ongkos_angkut' => $tujuan['ongkos_angkut'],
                        'harga_pt_sum' => $tujuan['harga_pt_sum'] ?? 0,
                        'jenis_kendaraan' => $tujuan['jenis_kendaraan'] ?? null,
                    ]);
                }
            }

            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function show(string $id) {}

    public function edit(string $id)
    {
        try {
            $data = Supplier::with('tujuans')->findOrFail(decrypt($id));
            $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();

            return view('pages.supplier.edit', compact('data', 'tujuans'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'initial' => 'required|string|max:20|unique:suppliers,initial,'.$id,
            'tujuans' => 'nullable|array',
            'tujuans.*.tujuan_id' => 'required|exists:tujuan,id',
            'tujuans.*.jenis_kendaraan' => 'nullable|string|max:100',
            'tujuans.*.ongkos_angkut' => 'required|numeric|min:0',
            'tujuans.*.harga_pt_sum' => 'nullable|numeric|min:0',
        ]);

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->update($request->only('nama', 'initial'));

            // Delete all existing supplier_tujuan records for this supplier
            \DB::table('supplier_tujuan')->where('supplier_id', $id)->delete();

            // Insert new data
            if ($request->has('tujuans')) {
                foreach ($request->tujuans as $tujuan) {
                    // Check if this exact combination already exists (safety check)
                    $exists = \DB::table('supplier_tujuan')
                        ->where('supplier_id', $id)
                        ->where('tujuan_id', $tujuan['tujuan_id'])
                        ->where('jenis_kendaraan', $tujuan['jenis_kendaraan'] ?? null)
                        ->exists();

                    if (! $exists) {
                        $supplier->tujuans()->attach($tujuan['tujuan_id'], [
                            'ongkos_angkut' => $tujuan['ongkos_angkut'],
                            'harga_pt_sum' => $tujuan['harga_pt_sum'] ?? 0,
                            'jenis_kendaraan' => $tujuan['jenis_kendaraan'] ?? null,
                        ]);
                    }
                }
            }

            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            Supplier::findOrFail($id)->delete();

            return response()->json(['success' => true, 'message' => 'Supplier berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }

    public function quickStore(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'initial' => 'required|string|max:20|unique:suppliers,initial',
        ]);

        try {
            $supplier = Supplier::create($request->only('nama', 'initial'));

            return response()->json([
                'success' => true,
                'supplier' => ['id' => $supplier->id, 'nama' => $supplier->nama, 'initial' => $supplier->initial],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get ongkos angkut untuk supplier, tujuan, dan jenis kendaraan tertentu
     */
    public function getOngkosAngkut(Request $request)
    {
        try {
            $supplierId = $request->supplier_id;
            $tujuanId = $request->tujuan_id;
            $jenisKendaraan = $request->jenis_kendaraan;

            if (! $supplierId || ! $tujuanId) {
                return response()->json(['success' => false, 'message' => 'Supplier dan Tujuan harus diisi'], 400);
            }

            $supplier = Supplier::with('tujuans')->findOrFail($supplierId);
            $ongkos = $supplier->getOngkosAngkut($tujuanId, $jenisKendaraan);
            $hargaPtSum = $supplier->getHargaPtSum($tujuanId, $jenisKendaraan);

            return response()->json([
                'success' => true,
                'ongkos_angkut' => $ongkos,
                'harga_pt_sum' => $hargaPtSum,
                'has_relation' => $ongkos > 0,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get list jenis kendaraan yang tersedia untuk supplier tertentu
     */
    public function getJenisKendaraan(Request $request)
    {
        try {
            $supplierId = $request->supplier_id;

            if (! $supplierId) {
                return response()->json(['success' => false, 'message' => 'Supplier harus diisi'], 400);
            }

            $supplier = Supplier::with('tujuans')->findOrFail($supplierId);

            $tujuans = [];
            $jenisKendaraan = [];
            // Map: tujuan_id -> jenis_kendaraan -> ongkos_angkut / harga_pt_sum
            $ongkosMap = [];
            $hargaPtSumMap = [];

            foreach ($supplier->tujuans as $tujuan) {
                // unique tujuan by id
                $tujuans[$tujuan->id] = [
                    'id' => $tujuan->id,
                    'nama' => $tujuan->nama,
                ];

                if (! empty($tujuan->pivot->jenis_kendaraan)) {
                    $jenisKendaraan[$tujuan->pivot->jenis_kendaraan] = $tujuan->pivot->jenis_kendaraan;
                }

                // Build ongkos map: [tujuan_id][jenis_kendaraan] = ongkos_angkut
                $jenis = $tujuan->pivot->jenis_kendaraan ?? '';
                if (! isset($ongkosMap[$tujuan->id])) {
                    $ongkosMap[$tujuan->id] = [];
                }
                if (! isset($hargaPtSumMap[$tujuan->id])) {
                    $hargaPtSumMap[$tujuan->id] = [];
                }
                $ongkosMap[$tujuan->id][$jenis] = (float) ($tujuan->pivot->ongkos_angkut ?? 0);
                $hargaPtSumMap[$tujuan->id][$jenis] = (float) ($tujuan->pivot->harga_pt_sum ?? 0);
            }

            return response()->json([
                'success' => true,
                'jenis_kendaraan' => array_values($jenisKendaraan),
                'tujuans' => array_values($tujuans),
                'ongkos_map' => $ongkosMap,
                'harga_pt_sum_map' => $hargaPtSumMap,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
