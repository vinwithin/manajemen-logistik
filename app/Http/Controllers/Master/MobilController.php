<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Mobil;
use App\Services\Datatables\MobilService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobilController extends Controller
{
    public function __construct(private MobilService $mobilService) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Mobil::query()->select('mobils.*');

            return $this->mobilService->getData($query);
        }

        return view('pages.mobil.index');
    }

    public function create()
    {
        return view('pages.mobil.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['nopol'] = $this->normalizeNopol($data['nopol']);
        $data['is_aktif'] = $request->has('is_aktif');

        try {
            Mobil::create($data);

            return redirect()->route('mobil.index')->with('success', 'Mobil berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function show(string $id) {}

    public function edit(string $id)
    {
        try {
            $data = Mobil::findOrFail(decrypt($id));

            return view('pages.mobil.edit', compact('data'));
        } catch (Exception $e) {
            return redirect()->route('mobil.index')->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $mobil = Mobil::findOrFail($id);
        $data = $this->validatedData($request, $mobil->id);
        $data['nopol'] = $this->normalizeNopol($data['nopol']);
        $data['is_aktif'] = $request->has('is_aktif');

        try {
            $mobil->update($data);

            return redirect()->route('mobil.index')->with('success', 'Mobil berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            Mobil::findOrFail($id)->delete();

            return response()->json(['success' => true, 'message' => 'Mobil berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus mobil.'], 500);
        }
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nopol' => [
                'required',
                'string',
                'max:20',
                Rule::unique('mobils', 'nopol')->ignore($ignoreId),
            ],
            'nama_sopir' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'is_aktif' => 'boolean',
        ]);
    }

    private function normalizeNopol(string $nopol): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $nopol)));
    }
}
