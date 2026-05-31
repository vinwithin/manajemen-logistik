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
        Schema::table('gudang_mutasi_stok', function (Blueprint $table) {
            $table->unsignedBigInteger('referensi_id')->nullable()->change();
            $table->integer('po_penerima_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gudang_mutasi_stok', function (Blueprint $table) {
            $table->unsignedBigInteger('referensi_id')->nullable(false)->change();
            $table->integer('po_penerima_id')->nullable(false)->change();
        });
    }
};
