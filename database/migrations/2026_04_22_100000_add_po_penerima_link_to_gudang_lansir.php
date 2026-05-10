<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add link from Gudang Lansir Penerima to PO Penerima
        Schema::table('gudang_lansir_penerima', function (Blueprint $table) {
            $table->foreignId('po_penerima_id')
                ->nullable()
                ->after('kendaraan_id')
                ->constrained('po_penerima')
                ->onDelete('set null')
                ->comment('Link ke PO Penerima (source data)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gudang_lansir_penerima', function (Blueprint $table) {
            $table->dropForeign(['po_penerima_id']);
            $table->dropColumn('po_penerima_id');
        });
    }
};
