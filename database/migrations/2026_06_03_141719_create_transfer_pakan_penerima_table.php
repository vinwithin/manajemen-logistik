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
        Schema::create('transfer_pakan_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kendaraan_id')->constrained('transfer_pakan_kendaraan')->cascadeOnDelete();
            $table->foreignId('penerima_id')->nullable()->constrained('penerima')->nullOnDelete();
            $table->string('nama_penerima', 255);
            $table->foreignId('tujuan_id')->nullable()->constrained('tujuan')->nullOnDelete();
            $table->enum('status', ['pending', 'tiba', 'selesai'])->default('pending');
            $table->string('bukti_tiba')->nullable();
            $table->timestamp('tiba_at')->nullable();
            $table->string('validasi_oleh');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pakan_penerima');
    }
};
