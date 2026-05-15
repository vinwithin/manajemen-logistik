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
        Schema::table('lansir_payments', function (Blueprint $table) {
            $table->foreignId('gudang_lansir_header_id')
                ->nullable()
                ->after('po_id')
                ->constrained('gudang_lansir_header')
                ->nullOnDelete()
                ->comment('Untuk pembayaran gudang lansir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lansir_payments', function (Blueprint $table) {
            $table->dropForeign(['gudang_lansir_header_id']);
            $table->dropColumn('gudang_lansir_header_id');
        });
    }
};
