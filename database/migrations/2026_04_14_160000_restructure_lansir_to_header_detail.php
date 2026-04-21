<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah po_item_lansir jadi header event lansir
        // Hapus kolom lama yang dipindah ke tabel detail
        Schema::table('po_item_lansir', function (Blueprint $table) {
            $table->string('validasi_oleh')->nullable()->after('po_item_id');
            // Hapus kolom yang akan dipindah ke tabel detail
            $table->dropColumn(['no_polisi_lansir', 'tim_bongkar', 'berat_lansir', 'ongkos_lansir', 'upah_bongkar']);
        });

        // Detail per mobil lansir
        Schema::create('po_lansir_mobil', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lansir_id');
            $table->string('no_polisi');
            $table->string('nama_sopir')->nullable();
            $table->decimal('berat', 10, 2)->nullable()->comment('kg');
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('ongkos', 15, 2)->nullable()->comment('Rp/kg');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('lansir_id')->references('id')->on('po_item_lansir')->onDelete('cascade');
        });

        // Detail per tim bongkar
        Schema::create('po_lansir_tim', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lansir_id');
            $table->string('nama_tim');
            $table->decimal('berat', 10, 2)->nullable()->comment('kg');
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('upah', 15, 2)->nullable()->comment('Rp/kg');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('lansir_id')->references('id')->on('po_item_lansir')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_lansir_tim');
        Schema::dropIfExists('po_lansir_mobil');
        Schema::table('po_item_lansir', function (Blueprint $table) {
            $table->dropColumn('validasi_oleh');
            $table->string('no_polisi_lansir')->nullable();
            $table->string('tim_bongkar')->nullable();
            $table->decimal('berat_lansir', 10, 2)->nullable();
            $table->decimal('ongkos_lansir', 15, 2)->nullable();
            $table->decimal('upah_bongkar', 15, 2)->nullable();
        });
    }
};
