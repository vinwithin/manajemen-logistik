<?php

namespace App\Services\Datatables;

use App\Models\User;
use Yajra\DataTables\DataTables;


class UserService
{
    public function getData($query)
    {
        return DataTables::of($query)

            ->addColumn('aktif', function ($q) {
                $html = '';
                if ($q->aktif === 1) {
                    $html = "<div class='badge bg-success'>Aktif</div>";
                } else {
                    $html = "<div class='badge bg-grey'>Tidak Aktif</div>";
                }
                return $html;
            })
            ->addColumn('action', function ($q) {
                return view('pages.user._action', compact('q'));
            })



            ->addColumn('roles', function (User $user) {
                return $user->roles->map(function ($roles) {
                    return "- " . $roles->name;
                })->implode('<br>');
            })
            ->addColumn('level_akun', function ($a) {
                if ($a->level_akun == '1') {
                    $level = "<span style='font-style:italic'>Pusat (Semua CV)</span>";
                } else {
                    $level = "<ul>";
                    foreach ($a->userCv as $p) {
                        $level .= "<li>" . $p->cv->nama_cv . '</li>';
                    }
                    $level .= "</ul>";
                }
                return $level;
            })

            ->addIndexColumn()
            ->escapeColumns('aktif', 'action', 'roles')->make(true);
    }
}
