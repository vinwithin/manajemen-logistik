<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Company;
use App\Models\Cv;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Cv::all()->keyBy('id');

        if ($companies->isEmpty()) {
            $this->command->error('Jalankan CompanySeeder terlebih dahulu!');
            return;
        }

        // ── Data users (sesuai struktur tabel klien) ───────────
        $usersData = [

            // ── Super Admin Pusat ──────────────────────────────
            [
                'name'           => 'Superadmin',
                'email'          => 'superadmin@gmail.com',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 1,
                'created_at'     => now(),
                'updated_at'     => now(),

                // Meta (tidak masuk DB langsung)
                '_role'          => 'super admin',
                '_companies'     => $companies->keys()->all(), // semua company
                '_company_role'  => 'super admin',
            ],
            [
                'name'           => 'Admin Pusat',
                'email'          => 'admin@sum-pusat.id',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 1,
                'created_at'     => now(),
                'updated_at'     => now(),

                // Meta (tidak masuk DB langsung)
                '_role'          => 'admin',
                '_companies'     => $companies->keys()->all(), // semua company
                '_company_role'  => 'admin',
            ],

            // ── Admin Jambi ────────────────────────────────────
            [
                'name'           => 'Admin Jambi',
                'email'          => 'admin@sum-jambi.id',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 2,
                'created_at'     => now(),
                'updated_at'     => now(),

                '_role'          => 'admin',
                '_companies'     => [1],
                '_company_role'  => 'admin',
            ],

            // ── Admin Kerinci ──────────────────────────────────
            [
                'name'           => 'Admin Kerinci',
                'email'          => 'admin@sum-kerinci.id',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 2,
                'created_at'     => now(),
                'updated_at'     => now(),

                '_role'          => 'admin',
                '_companies'     => [2],
                '_company_role'  => 'admin',
            ],

            // ── Admin Muara Bungo ──────────────────────────────
            [
                'name'           => 'Admin Muara Bungo',
                'email'          => 'admin@sum-bungo.id',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 2,
                'created_at'     => now(),
                'updated_at'     => now(),

                '_role'          => 'admin',
                '_companies'     => [3],
                '_company_role'  => 'admin',
            ],

            [
                'name'           => 'Admin Gudang TR',
                'email'          => 'admin@sum-tanjab.id',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 2,
                'created_at'     => now(),
                'updated_at'     => now(),

                '_role'          => 'admin',
                '_companies'     => [4],
                '_company_role'  => 'admin',
            ],


            // ── Viewer Pusat ───────────────────────────────────
            [
                'name'           => 'Viewer Pusat',
                'email'          => 'viewer@sum-pusat.id',
                'password'       => Hash::make('password'),
                'aktif'          => true,
                'level'          => 2,
                'created_at'     => now(),
                'updated_at'     => now(),

                '_role'          => 'viewer',
                '_companies'     => $companies->keys()->all(), // semua, tapi viewer
                '_company_role'  => 'viewer',
            ],
        ];

        // ── Insert users & assign roles + user_companies ───────
        $userCompanyRecords = [];

        foreach ($usersData as $userData) {
            // Pisahkan meta dari data DB
            $role            = $userData['_role'];
            $companyCodes    = $userData['_companies'];
            $defaultRole     = $userData['_company_role'];
            $companiesDetail = $userData['_companies_detail'] ?? [];

            unset(
                $userData['_role'],
                $userData['_companies'],
                $userData['_company_role'],
                $userData['_companies_detail']
            );

            // Buat / ambil user
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Assign Spatie role
            $user->syncRoles([$role]);

            // Assign user_companies
            foreach ($companyCodes as $code) {
                $company = $companies->get($code);

                if (! $company) {
                    $this->command->warn("  Company code '{$code}' tidak ditemukan, skip.");
                    continue;
                }

                // Cek apakah ada override role per company
                $companyRole = $companiesDetail[$code] ?? $defaultRole;

                $userCompanyRecords[] = [
                    'user_id'     => $user->id,
                    'cv_id'  => $company->id,
                    'role'        => $companyRole,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            $this->command->line("  ✓ {$user->name} [{$role}] → " . implode(', ', $companyCodes));
        }

        // Bulk insert user_companies (hindari duplikat)
        foreach ($userCompanyRecords as $record) {
            DB::table('user_cv')->updateOrInsert(
                ['user_id' => $record['user_id'], 'cv_id' => $record['cv_id']],
                $record
            );
        }

     
    }
}
