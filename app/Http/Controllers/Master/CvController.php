<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Services\Datatables\CvService;
use Exception;
use Illuminate\Http\Request;

class CvController extends Controller
{
    protected $cvService;

    public function __construct(CvService $cvService)
    {
        $this->cvService = $cvService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Cv::select(['id', 'nama_cv', 'code', 'is_aktif']);
            return $this->cvService->getData($query);
        }

        $cvList = Cv::withOmzet();
        return view('pages.cv.index', compact('cvList'));
    }

    public function create()
    {
        return view('pages.cv.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_cv' => 'required|string|max:255',
            'code'    => 'nullable|string|max:50|unique:cv,code',
        ]);

        try {
            Cv::create([
                'nama_cv'  => $request->nama_cv,
                'code'     => $request->code,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
            ]);

            return redirect()->route('perusahaan.index')->with('success', 'Perusahaan berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        try {
            $id   = decrypt($id);
            $data = Cv::findOrFail($id);
            return view('pages.cv.edit', compact('data'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_cv' => 'required|string|max:255',
            'code'    => 'nullable|string|max:50|unique:cv,code,' . $id,
        ]);

        try {
            $cv = Cv::findOrFail($id);
            $cv->update([
                'nama_cv'  => $request->nama_cv,
                'code'     => $request->code,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
            ]);

            return redirect()->route('perusahaan.index')->with('success', 'Perusahaan berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            Cv::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Perusahaan berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }
}
