<?php

namespace App\Traits;

use App\Models\Tujuan;
use Illuminate\Support\Facades\Auth;

trait WithUserTujuan
{
    /**
     * Ambil userTujuan yang difilter sesuai level_tujuan user
     */
    protected function getUserTujuan()
    {
        $user = Auth::user();
        
        if (!$user) {
            return collect();
        }

        $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
        
        // Filter sesuai level_tujuan user
        if ($user->level_tujuan != 1) {
            $tujuans = $tujuans->whereIn('id', $user->userTujuan->pluck('tujuan_id'));
        }

        return $tujuans;
    }
}
