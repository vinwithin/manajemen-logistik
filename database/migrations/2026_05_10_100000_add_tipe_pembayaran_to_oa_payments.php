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
        Schema::table('oa_payments', function (Blueprint $table) {
            $table->enum('tipe_pembayaran', ['oa', 'dp_supplier'])
                ->default('oa')
                ->after('po_penerima_id')
                ->comment('Tipe pembayaran: oa = pembayaran OA penerima, dp_supplier = down payment ke supplier');
            
            $table->foreignId('po_kendaraan_id')
                ->nullable()
                ->after('po_penerima_id')
                ->constrained('po_kendaraan')
                ->nullOnDelete()
                ->comment('Untuk pembayaran DP supplier (tipe_pembayaran = dp_supplier)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oa_payments', function (Blueprint $table) {
            $table->dropForeign(['po_kendaraan_id']);
            $table->dropColumn(['tipe_pembayaran', 'po_kendaraan_id']);
        });
    }
};
