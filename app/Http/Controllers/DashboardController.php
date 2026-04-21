<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeCv = session('active_cv');

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

        return view('pages.dashboard', compact('userCvs', 'selectedCv', 'activeCv'));
    }

    public function switchCv(Request $request)
    {
        $cvId = $request->cv_id;

        if ($cvId === 'all' || ! $cvId) {
            session()->forget('active_cv');
        } else {
            session(['active_cv' => (int) $cvId]);
        }

        return redirect()->back();
    }
}
