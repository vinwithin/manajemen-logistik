<?php

namespace App\Providers;

use App\Helpers\MenuHelper;
use App\Models\Cv;
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
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $view->with('sidebarMenus', MenuHelper::accessibleMenus());

                // Share CV context ke semua views
                $activeCvId = session('active_cv');

                if ($user->level == 1) {
                    $userCvs = Cv::where('is_aktif', true)->get();
                } else {
                    $userCvs = Cv::whereIn('id', $user->userCV->pluck('cv_id'))
                        ->where('is_aktif', true)->get();
                }

                $activeCv = $activeCvId ? $userCvs->firstWhere('id', $activeCvId) : null;

                $view->with('userCvs', $userCvs);
                $view->with('activeCv', $activeCv);
            }
        });
    }
}
