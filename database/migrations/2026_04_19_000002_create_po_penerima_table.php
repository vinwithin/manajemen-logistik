<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_kendaraan_id')->constrained('po_kendaraan')->cascadeOnDelete();
            $table->string('nama_penerima');
            $table->foreignId('tujuan_id')->nullable()->constrained('tujuan')->nullOnDelete();
            $table->enum('status', ['pending', 'berangkat', 'tiba', 'selesai', 'batal'])->default('pending');
            $table->string('bukti_tiba')->nullable();
            $table->string('validasi_oleh')->nullable();
            $table->timestamp('tiba_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_penerima');
    }
};
