<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tujuan;
use App\Models\Penerima;
use App\Traits\WithUserTujuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use WithUserTujuan;
    public function index()
    {
        $user = Auth::user();
        $activeCv = session('active_cv');
        $tujuans = $this->getUserTujuan();

        if ($user->level == 1) {
            $userCvs = Cv::where('is_aktif', true)->get();
        } else {
            $userCvs = Cv::whereIn('id', $user->userCV->pluck('cv_id'))->where('is_aktif', true)->get();
        }

        if ($activeCv && ! $userCvs->contains('id', $activeCv)) {
            session()->forget('active_cv');
            $activeCv = null;
        }

        $selectedCv = $activeCv ? $userCvs->firstWhere('id', $activeCv) : null;

        // Hitung statistik
        $totalPO = PurchaseOrder::with('kendaraans.penerimas.penerima');
        $totalSupplier = Supplier::query();
        $totalTujuan = Tujuan::query();
        $totalPenerima = Penerima::query();

        // Filter jika ada selectedCv
        if ($selectedCv) {
            $totalPO->where('cv_id', $selectedCv->id)
            ->where(function ($q) use ($tujuans) {
                        $q->whereHas('kendaraans.penerimas.penerima', function ($q) use ($tujuans) {
                            $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                        })
                        ->orWhereDoesntHave('kendaraans.penerimas');
                    });
            $totalTujuan->where('cv_id', $selectedCv->id);
            $totalPenerima->whereHas('tujuan', function ($q) use ($selectedCv) {
                $q->where('cv_id', $selectedCv->id);
            });
        }

        $totalPO = $totalPO->count();
        $totalSupplier = $totalSupplier->count();
        $totalTujuan = $totalTujuan->where('is_aktif', true)->count();
        $totalPenerima = $totalPenerima->where('is_aktif', true)->count();

        return view('pages.dashboard', compact(
            'userCvs', 
            'selectedCv', 
            'activeCv',
            'totalPO',
            'totalSupplier',
            'totalTujuan',
            'totalPenerima'
        ));
    }

    public function switchCv(Request $request)
    {
        $cvId  = $request->cv_id;
        $user  = Auth::user();

        if ($user->level == 1) {
            $allowedCvs = Cv::where('is_aktif', true)->pluck('id');
        } else {
            $allowedCvs = $user->userCV->pluck('cv_id');
        }

        if ($cvId === 'all' || ! $cvId) {
            // "Semua Perusahaan" hanya boleh untuk admin (level 1)
            if ($user->level == 1) {
                session()->forget('active_cv');
            }
            // Non-admin tidak bisa switch ke "semua" — abaikan
        } else {
            if ($allowedCvs->contains((int) $cvId)) {
                session(['active_cv' => (int) $cvId]);
            }
        }

        return redirect()->back();
    }
}
