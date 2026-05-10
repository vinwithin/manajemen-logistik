<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rugi_laba_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rugi_laba_id')->constrained('rugi_laba')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('kode_biaya', 60)->comment('Nama field biaya, misal: gaji, bbm, dll');
            $table->string('keterangan')->nullable();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rugi_laba_id', 'tanggal']);
            $table->index(['rugi_laba_id', 'kode_biaya']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rugi_laba_harian');
    }
};
