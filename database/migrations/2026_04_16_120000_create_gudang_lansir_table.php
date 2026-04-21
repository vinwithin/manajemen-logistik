<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_lansir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tujuan_id')->constrained('tujuan');
            $table->foreignId('kode_pakan_id')->constrained('kode_pakan');
            $table->decimal('jumlah_kg', 12, 2);
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('ongkos_per_kg', 15, 2)->nullable();
            $table->decimal('upah_per_kg', 15, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_lansir');
    }
};
