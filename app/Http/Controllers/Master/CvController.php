<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Services\Datatables\CvService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'code' => 'nullable|string|max:50|unique:cv,code',
            'alamat' => 'nullable|string|max:500',
            'nama_bank' => 'nullable|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'atas_nama_rekening' => 'nullable|string|max:255',
            'nama_pimpinan' => 'nullable|string|max:255',
            'no_dokumen_prefix' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        try {
            $data = [
                'nama_cv' => $request->nama_cv,
                'code' => $request->code,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
                'alamat' => $request->alamat,
                'nama_bank' => $request->nama_bank,
                'no_rekening' => $request->no_rekening,
                'atas_nama_rekening' => $request->atas_nama_rekening,
                'nama_pimpinan' => $request->nama_pimpinan,
                'no_dokumen_prefix' => $request->no_dokumen_prefix,
            ];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('logos', $filename, 'public');
                $data['logo'] = $path;
            }

            Cv::create($data);

            return redirect()->route('perusahaan.index')->with('success', 'Perusahaan berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        try {
            $id = decrypt($id);
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
            'code' => 'nullable|string|max:50|unique:cv,code,'.$id,
            'alamat' => 'nullable|string|max:500',
            'nama_bank' => 'nullable|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'atas_nama_rekening' => 'nullable|string|max:255',
            'nama_pimpinan' => 'nullable|string|max:255',
            'no_dokumen_prefix' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        try {
            $cv = Cv::findOrFail($id);

            $data = [
                'nama_cv' => $request->nama_cv,
                'code' => $request->code,
                'is_aktif' => $request->has('is_aktif') ? 1 : 0,
                'alamat' => $request->alamat,
                'nama_bank' => $request->nama_bank,
                'no_rekening' => $request->no_rekening,
                'atas_nama_rekening' => $request->atas_nama_rekening,
                'nama_pimpinan' => $request->nama_pimpinan,
                'no_dokumen_prefix' => $request->no_dokumen_prefix,
            ];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Hapus logo lama jika ada
                if ($cv->logo && Storage::disk('public')->exists($cv->logo)) {
                    Storage::disk('public')->delete($cv->logo);
                }

                $file = $request->file('logo');
                $filename = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('logos', $filename, 'public');
                $data['logo'] = $path;
            }

            // Handle hapus logo
            if ($request->has('hapus_logo') && $request->hapus_logo == '1') {
                if ($cv->logo && Storage::disk('public')->exists($cv->logo)) {
                    Storage::disk('public')->delete($cv->logo);
                }
                $data['logo'] = null;
            }

            $cv->update($data);

            return redirect()->route('perusahaan.index')->with('success', 'Perusahaan berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $cv = Cv::findOrFail($id);

            // Hapus logo jika ada
            if ($cv->logo && Storage::disk('public')->exists($cv->logo)) {
                Storage::disk('public')->delete($cv->logo);
            }

            $cv->delete();

            return response()->json(['success' => true, 'message' => 'Perusahaan berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }
}
