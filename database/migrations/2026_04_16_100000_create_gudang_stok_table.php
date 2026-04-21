<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tujuan_id')->constrained('tujuan');
            $table->foreignId('kode_pakan_id')->constrained('kode_pakan');
            $table->decimal('stok_kg', 12, 2)->default(0);
            $table->integer('stok_karung')->default(0);
            $table->timestamps();

            $table->unique(['tujuan_id', 'kode_pakan_id'], 'uq_gudang_stok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_stok');
    }
};
