<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename tabel lama untuk backup
        Schema::rename('gudang_lansir', 'gudang_lansir_old');
        Schema::rename('gudang_lansir_mobil', 'gudang_lansir_mobil_old');
        Schema::rename('gudang_lansir_tim', 'gudang_lansir_tim_old');

        // Level 1: Kendaraan
        Schema::create('gudang_lansir_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('tujuan')->comment('Gudang asal');
            $table->string('no_polisi', 20);
            $table->string('nama_sopir')->nullable();
            $table->string('no_surat_jalan', 100)->nullable();
            $table->date('tanggal_lansir');
            $table->decimal('total_kg', 12, 2)->default(0)->comment('Total muatan kendaraan');
            $table->integer('total_karung')->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Level 2: Penerima
        Schema::create('gudang_lansir_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kendaraan_id')->constrained('gudang_lansir_kendaraan')->onDelete('cascade');
            $table->string('nama_penerima');
            $table->foreignId('tujuan_id')->nullable()->constrained('tujuan')->comment('Tujuan penerima');
            $table->timestamps();
        });

        // Level 2b: Tim Bongkar (per penerima)
        Schema::create('gudang_lansir_tim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerima_id')->constrained('gudang_lansir_penerima')->onDelete('cascade');
            $table->string('nama_tim');
            $table->decimal('upah_per_kg', 15, 2)->nullable();
            $table->timestamps();
        });

        // Level 3: Pakan (per penerima)
        Schema::create('gudang_lansir_pakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerima_id')->constrained('gudang_lansir_penerima')->onDelete('cascade');
            $table->foreignId('kode_pakan_id')->constrained('kode_pakan');
            $table->decimal('jumlah_kg', 12, 2);
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('ongkos_oa', 15, 2)->nullable()->comment('Ongkos angkut per kg');
            $table->timestamps();

            // Unique constraint: satu penerima tidak boleh punya kode pakan yang sama 2x
            $table->unique(['penerima_id', 'kode_pakan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_lansir_pakan');
        Schema::dropIfExists('gudang_lansir_tim');
        Schema::dropIfExists('gudang_lansir_penerima');
        Schema::dropIfExists('gudang_lansir_kendaraan');

        // Restore tabel lama
        Schema::rename('gudang_lansir_old', 'gudang_lansir');
        Schema::rename('gudang_lansir_mobil_old', 'gudang_lansir_mobil');
        Schema::rename('gudang_lansir_tim_old', 'gudang_lansir_tim');
    }
};
