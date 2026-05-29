<?php

namespace App\Providers;

use App\Helpers\MenuHelper;
use App\Models\Cv;
use App\Models\Tujuan;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $view->with('sidebarMenus', MenuHelper::accessibleMenus());

                // Share CV context ke semua views
                $activeCvId = session('active_cv');

                // Ambil semua CV dengan omzet
                $userCvs = Cv::withOmzet();
                
                // if ($user->level == 1) {
                //     $userCvs = Cv::where('is_aktif', true)->get();
                // } else {
                //     $userCvs = Cv::whereIn('id', $user->userCV->pluck('cv_id'))
                //         ->where('is_aktif', true)->get();
                
                // Filter sesuai level user
                if ($user->level != 1) {
                    $userCvs = $userCvs->whereIn('id', $user->userCV->pluck('cv_id'));
                }

                // Jika user hanya punya 1 CV dan belum ada session, otomatis set ke CV itu
                if ($userCvs->count() === 1 && ! $activeCvId) {
                    $activeCvId = $userCvs->first()->id;
                    session(['active_cv' => $activeCvId]);
                }

                $activeCv = $activeCvId ? $userCvs->firstWhere('id', $activeCvId) : null;

                // Hanya admin (level 1) yang boleh melihat opsi "Semua Perusahaan"
                $canSeeAllCv = $user->level == 1;

                // Share Tujuan context ke semua views
                $userTujuan = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
                
                // Filter sesuai level_tujuan user
                if ($user->level_tujuan != 1) {
                    $userTujuan = $userTujuan->whereIn('id', $user->userTujuan->pluck('tujuan_id'));
                }

                $view->with('userCvs', $userCvs);
                $view->with('activeCv', $activeCv);
                $view->with('canSeeAllCv', $canSeeAllCv);
                $view->with('userTujuan', $userTujuan);
            }
        });
    }
}
