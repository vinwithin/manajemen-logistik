<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_mutasi_stok', function (Blueprint $table) {
            // Tambah kolom po_penerima_id (nullable karena ada data lama dari po_item)
            $table->foreignId('po_penerima_id')
                ->nullable()
                ->after('referensi_id')
                ->constrained('po_penerima')
                ->nullOnDelete();
            
            // Update enum referensi_tipe untuk include 'po_penerima_pakan'
            DB::statement("ALTER TABLE gudang_mutasi_stok MODIFY COLUMN referensi_tipe ENUM('po_item', 'po_penerima_pakan', 'lansir_gudang', 'lansir_gudang_kendaraan')");
        });
    }

    public function down(): void
    {
        Schema::table('gudang_mutasi_stok', function (Blueprint $table) {
            $table->dropForeign(['po_penerima_id']);
            $table->dropColumn('po_penerima_id');
        });
        
        // Kembalikan enum ke nilai awal
        DB::statement("ALTER TABLE gudang_mutasi_stok MODIFY COLUMN referensi_tipe ENUM('po_item', 'lansir_gudang')");
    }
};
