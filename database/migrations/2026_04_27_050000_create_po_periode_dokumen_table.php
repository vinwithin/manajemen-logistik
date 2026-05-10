<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_periode_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->nullable()->constrained('cv')->cascadeOnDelete();
            $table->date('dari');
            $table->date('sampai');
            $table->unsignedSmallInteger('urutan')->default(1)->comment('Nomor urut per CV per tahun, digunakan sebagai prefix nomor surat');
            $table->string('no_surat')->comment('Nomor surat lengkap, misal: 4-TR-JBI/GJ/III/2026');
            $table->enum('tipe', ['ptsum', 'supplier', 'gudang_ptsum', 'gudang_supplier'])->default('ptsum');
            $table->string('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cv_id', 'dari', 'sampai', 'tipe'], 'unique_dokumen_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_periode_dokumen');
    }
};
