<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CvSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'nama_cv'       => 'CV. Hrz Cahaya Logistic',
                'code'       => 'Hrz',
                'is_aktif'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cv'       => 'CV. TR Argo Lestari',
                'code'       => 'TR',
                'is_aktif'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cv'       => 'CV. Hanun Nusa Natara',
                'code'       => 'HNN',
                'is_aktif'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cv'       => 'CV. Horiansa Trans Gemilang',
                'code'       => 'HTG',
                'is_aktif'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('cv')->insert($companies);
    }
}
