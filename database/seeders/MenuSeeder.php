<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate dulu agar idempotent
        DB::table('menus')->truncate();

        // ── PARENT MENUS (parent_id = 0 = tidak punya parent) ──
        $parents = [
            [
                'id' => 1,
                'nama_menu' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'grid',
                'parent_id' => '0',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nama_menu' => 'Purchase Order',
                'url' => '/purchase-order',
                'icon' => 'file-text',
                'parent_id' => '0',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nama_menu' => 'Keuangan',
                'url' => '/keuangan',
                'icon' => 'dollar-sign',
                'parent_id' => '0',
                'order' => '5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'nama_menu' => 'Master Data',
                'url' => '/master',
                'icon' => 'database',
                'parent_id' => '0',
                'order' => '6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'nama_menu' => 'Laporan',
                'url' => '/laporan',
                'icon' => 'bar-chart-2',
                'parent_id' => '0',
                'order' => '7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'nama_menu' => 'Pengaturan',
                'url' => '/pengaturan',
                'icon' => 'settings',
                'parent_id' => '0',
                'order' => '8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'nama_menu' => 'Gudang',
                'url' => '/gudang',
                'icon' => 'archive',
                'parent_id' => '0',
                'order' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'nama_menu' => 'Transfer Pakan',
                'url' => '/transfer-pakan',
                'icon' => 'send',
                'parent_id' => '0',
                'order' => '4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'nama_menu' => 'GPS Tracking',
                'url' => '/gps',
                'icon' => 'navigation',
                'parent_id' => '0',
                'order' => '5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // ── CHILD MENUS ────────────────────────────────────────
        $children = [
            // ── PO (parent_id = 2) ─────────────────────────────
            [
                'id' => 10,
                'nama_menu' => 'Input PO',
                'url' => '/purchase-order/create',
                'icon' => 'plus-circle',
                'parent_id' => '2',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'nama_menu' => 'Daftar PO',
                'url' => '/purchase-order',
                'icon' => 'list',
                'parent_id' => '2',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'nama_menu' => 'Riwayat Lansir',
                'url' => '/purchase-order/lansir',
                'icon' => 'truck',
                'parent_id' => '2',
                'order' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── Keuangan (parent_id = 3) ───────────────────────
            [
                'id' => 20,
                'nama_menu' => 'Rekap OA Supplier',
                'url' => '/keuangan/oa',
                'icon' => 'truck',
                'parent_id' => '3',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'nama_menu' => 'Pembayaran Suplier',
                'url' => '/keuangan/pembayaran',
                'icon' => 'credit-card',
                'parent_id' => '3',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 22,
                'nama_menu' => 'Rekap Lansir',
                'url' => '/keuangan/rekap-lansir',
                'icon' => 'list',
                'parent_id' => '3',
                'order' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 23,
                'nama_menu' => 'Rugi Laba',
                'url' => '/keuangan/rugi-laba',
                'icon' => 'dollar-sign',
                'parent_id' => '3',
                'order' => '4',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── Master Data (parent_id = 4) ────────────────────
            [
                'id' => 30,
                'nama_menu' => 'Suplier (CV)',
                'url' => '/master/supplier',
                'icon' => 'users',
                'parent_id' => '4',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 31,
                'nama_menu' => 'Kode Pakan',
                'url' => '/master/pakan',
                'icon' => 'package',
                'parent_id' => '4',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 32,
                'nama_menu' => 'Tujuan Pengiriman',
                'url' => '/master/tujuan',
                'icon' => 'map-pin',
                'parent_id' => '4',
                'order' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 33,
                'nama_menu' => 'Penerima',
                'url' => '/master/penerima',
                'icon' => 'map-pin',
                'parent_id' => '4',
                'order' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── Laporan (parent_id = 5) ────────────────────────
            [
                'id' => 40,
                'nama_menu' => 'Laporan PO',
                'url' => '/laporan/po',
                'icon' => 'file',
                'parent_id' => '5',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 41,
                'nama_menu' => 'Laporan Pembayaran',
                'url' => '/laporan/pembayaran',
                'icon' => 'file-minus',
                'parent_id' => '5',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── Pengaturan (parent_id = 6) ─────────────────────
            [
                'id' => 50,
                'nama_menu' => 'Manajemen User',
                'url' => '/pengaturan/user',
                'icon' => 'user',
                'parent_id' => '6',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 51,
                'nama_menu' => 'Manajemen Perusahaan',
                'url' => '/pengaturan/perusahaan',
                'icon' => 'briefcase',
                'parent_id' => '6',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 52,
                'nama_menu' => 'Role & Permission',
                'url' => '/pengaturan/role',
                'icon' => 'shield',
                'parent_id' => '6',
                'order' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── Gudang (parent_id = 7) ─────────────────────────
            [
                'id' => 60,
                'nama_menu' => 'Stok Gudang',
                'url' => '/gudang/stok',
                'icon' => 'box',
                'parent_id' => '7',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 61,
                'nama_menu' => 'Lansir Gudang',
                'url' => '/gudang/lansir',
                'icon' => 'truck',
                'parent_id' => '7',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── Transfer Pakan (parent_id = 9) ────────────────
            [
                'id' => 90,
                'nama_menu' => 'Daftar Transfer',
                'url' => '/transfer-pakan',
                'icon' => 'list',
                'parent_id' => '9',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'nama_menu' => 'Input Transfer',
                'url' => '/transfer-pakan/create',
                'icon' => 'plus-circle',
                'parent_id' => '9',
                'order' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ── GPS Tracking (parent_id = 8) ───────────────────
            [
                'id' => 70,
                'nama_menu' => 'Tracking Kendaraan',
                'url' => '/gps',
                'icon' => 'map-pin',
                'parent_id' => '8',
                'order' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('menus')->insert(array_merge($parents, $children));

        $total = count($parents) + count($children);
        $this->command->info("✓ Menus seeded: {$total} menu (".count($parents).' parent, '.count($children).' child)');
    }
}
