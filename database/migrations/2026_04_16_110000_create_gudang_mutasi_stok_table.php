<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tujuan_id')->constrained('tujuan');
            $table->foreignId('kode_pakan_id')->constrained('kode_pakan');
            $table->enum('tipe', ['masuk', 'keluar']);
            $table->decimal('jumlah_kg', 12, 2);
            $table->integer('jumlah_karung')->default(0);
            $table->string('referensi_tipe');
            $table->unsignedBigInteger('referensi_id');
            $table->integer('po_penerima_id');
            $table->unsignedBigInteger('gudang_lansir_pakan_id')->nullable();
            $table->decimal('saldo_kg_after', 12, 2);
            $table->integer('saldo_karung_after')->default(0);
            $table->timestamps();

            $table->index(['tujuan_id', 'kode_pakan_id'], 'idx_mutasi_tujuan_pakan');
            $table->index('created_at', 'idx_mutasi_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_mutasi_stok');
    }
};
