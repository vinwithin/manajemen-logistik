<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update enum untuk referensi_tipe
        DB::statement("ALTER TABLE gudang_mutasi_stok MODIFY COLUMN referensi_tipe ENUM('po_item', 'po_penerima_pakan', 'lansir_gudang', 'lansir_gudang_kendaraan', 'lansir_gudang_header') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE gudang_mutasi_stok MODIFY COLUMN referensi_tipe ENUM('po_item', 'po_penerima_pakan', 'lansir_gudang', 'lansir_gudang_kendaraan') NOT NULL");
    }
};
