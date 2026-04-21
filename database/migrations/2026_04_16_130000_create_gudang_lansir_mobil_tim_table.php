<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_lansir_mobil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lansir_id')->constrained('gudang_lansir')->onDelete('cascade');
            $table->string('no_polisi', 20);
            $table->decimal('berat', 10, 2)->nullable();
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('ongkos', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('gudang_lansir_tim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lansir_id')->constrained('gudang_lansir')->onDelete('cascade');
            $table->string('nama_tim', 255);
            $table->decimal('berat', 10, 2)->nullable()->comment('kg');
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('upah', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_lansir_tim');
        Schema::dropIfExists('gudang_lansir_mobil');
    }
};
