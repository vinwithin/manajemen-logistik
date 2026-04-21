<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class permissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions + menu_id ──────────────────────────────
        // Format: 'nama_permission' => menu_id (merujuk ke MenuSeeder)
        //
        // Menu ID map:
        //  1  = Dashboard
        //  10 = Input PO        (child of PO)
        //  11 = Daftar PO       (child of PO)
        //  20 = Rekap OA        (child of Keuangan)
        //  21 = Pembayaran      (child of Keuangan)
        //  22 = Rekap Lansir    (child of Keuangan)
        //  30 = Suplier CV      (child of Master Data)
        //  31 = Kode Pakan      (child of Master Data)
        //  32 = Tujuan Kirim    (child of Master Data)
        //  40 = Laporan PO      (child of Laporan)
        //  41 = Laporan Bayar   (child of Laporan)
        //  50 = Manaj. User     (child of Pengaturan)
        //  51 = Manaj. Company  (child of Pengaturan)
        //  52 = Role Permission (child of Pengaturan)

        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view',        'menu_id' => 1],

            // Purchase Order
            ['name' => 'po.view',               'menu_id' => 11],
            ['name' => 'po.create',             'menu_id' => 10],
            ['name' => 'po.edit',               'menu_id' => 11],
            ['name' => 'po.delete',             'menu_id' => 11],
            ['name' => 'lansir.view',             'menu_id' => 12],
            ['name' => 'lansir.create',             'menu_id' => 12],
            ['name' => 'lansir.edit',             'menu_id' => 12],

            // Keuangan
            ['name' => 'oa.view',               'menu_id' => 20],
            ['name' => 'payment.view',          'menu_id' => 21],
            ['name' => 'payment.create',        'menu_id' => 21],
            ['name' => 'payment.confirm',       'menu_id' => 21],
            ['name' => 'rekap-lansir.view',     'menu_id' => 22],
            ['name' => 'rekap-lansir.bayar',    'menu_id' => 22],

            // Master Data
            ['name' => 'supplier.view',         'menu_id' => 30],
            ['name' => 'supplier.create',       'menu_id' => 30],
            ['name' => 'supplier.edit',         'menu_id' => 30],
            ['name' => 'supplier.delete',       'menu_id' => 30],
            ['name' => 'feed_code.view',        'menu_id' => 31],
            ['name' => 'feed_code.manage',      'menu_id' => 31],
            ['name' => 'destination.view',      'menu_id' => 32],
            ['name' => 'destination.manage',    'menu_id' => 32],

            // Laporan
            ['name' => 'report.po.view',        'menu_id' => 40],
            ['name' => 'report.po.export',      'menu_id' => 40],
            ['name' => 'report.payment.view',   'menu_id' => 41],
            ['name' => 'report.payment.export', 'menu_id' => 41],

            // Pengaturan
            ['name' => 'user.view',             'menu_id' => 50],
            ['name' => 'user.create',           'menu_id' => 50],
            ['name' => 'user.edit',             'menu_id' => 50],
            ['name' => 'user.delete',           'menu_id' => 50],
            ['name' => 'company.view',          'menu_id' => 51],
            ['name' => 'company.create',        'menu_id' => 51],
            ['name' => 'company.edit',          'menu_id' => 51],
            ['name' => 'company.delete',        'menu_id' => 51],
            ['name' => 'role.view',             'menu_id' => 52],
            ['name' => 'role.manage',           'menu_id' => 52],

            // Gudang Stok
            ['name' => 'gudang-stok.view',      'menu_id' => 60],
            ['name' => 'gudang-stok.lansir',    'menu_id' => 61],
        ];

        // Insert/update permissions dengan menu_id
        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['menu_id' => $perm['menu_id']]
            );
        }

        $this->command->info('✓ Permissions seeded: ' . count($permissions) . ' permission');

        // ── Roles + assign permissions ─────────────────────────

        // Super Admin → semua permission
        $superAdmin = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
        $adminPusat = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $adminPusat->syncPermissions([
            'dashboard.view',
            'po.view',
            'po.create',
            'po.edit',
            'po.delete',
            'lansir.view',
            'lansir.create',
            'lansir.edit',
            'oa.view',
            'payment.view',
            'payment.create',
            'payment.confirm',
            'supplier.view',
            'supplier.create',
            'supplier.edit',
            'feed_code.view',
            'feed_code.manage',
            'destination.view',
            'destination.manage',
            'report.po.view',
            'report.po.export',
            'report.payment.view',
            'report.payment.export',
            'gudang-stok.view',
            'gudang-stok.lansir',
        ]);

        // Viewer → semua view, tanpa create/edit/delete/export
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'dashboard.view',
            'po.view',
            'oa.view',
            'payment.view',
            'supplier.view',
            'feed_code.view',
            'destination.view',
            'report.po.view',
            'report.payment.view',
        ]);

        $this->command->info('✓ Roles seeded:');
        $this->command->line('  super_admin  → ' . $superAdmin->permissions->count() . ' permissions');
        $this->command->line('  viewer       → ' . $viewer->permissions->count() . ' permissions');
    }
}
