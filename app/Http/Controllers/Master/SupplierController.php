<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
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
        return view('pages.supplier.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'    => 'required|string|max:255',
            'initial' => 'required|string|max:20|unique:suppliers,initial',
        ]);

        try {
            Supplier::create($request->only('nama', 'initial'));
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(string $id) {}

    public function edit(string $id)
    {
        try {
            $data = Supplier::findOrFail(decrypt($id));
            return view('pages.supplier.edit', compact('data'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama'    => 'required|string|max:255',
            'initial' => 'required|string|max:20|unique:suppliers,initial,' . $id,
        ]);

        try {
            Supplier::findOrFail($id)->update($request->only('nama', 'initial'));
            return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: ' . $e->getMessage())->withInput();
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
            'nama'    => 'required|string|max:255',
            'initial' => 'required|string|max:20|unique:suppliers,initial',
        ]);

        try {
            $supplier = Supplier::create($request->only('nama', 'initial'));
            return response()->json([
                'success'  => true,
                'supplier' => ['id' => $supplier->id, 'nama' => $supplier->nama, 'initial' => $supplier->initial],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
