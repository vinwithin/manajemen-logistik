<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_po')->unique();
            $table->date('tanggal_po');
            $table->unsignedBigInteger('cv_id')->nullable();
            $table->enum('status', ['draft', 'locked'])->default('draft');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_id');
            $table->string('no_polisi');
            $table->string('no_surat_jalan')->nullable();
            $table->unsignedBigInteger('tujuan_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('kode_pakan_id')->nullable();
            $table->string('nama_penerima')->nullable();
            $table->string('nama_supir')->nullable();
            $table->string('hp_supir')->nullable();
            $table->decimal('berat', 10, 2)->nullable()->comment('kg');
            $table->integer('jumlah_karung')->nullable()->comment('Jumlah karung');
            $table->decimal('ongkos', 15, 2)->nullable()->comment('Rp/kg');
            $table->string('status')->default('pending');
            $table->timestamp('tiba_at')->nullable()->comment('Waktu tiba di lokasi');
            $table->string('validasi_oleh')->nullable()->comment('Nama admin cabang yang validasi');
            $table->string('bukti_tiba')->nullable()->comment('Path file bukti tiba');
            $table->timestamp('selesai_lansir_at')->nullable();
            $table->timestamps();

            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('kode_pakan_id')->references('id')->on('kode_pakan')->nullOnDelete();
        });

        // Tabel penerima (bisa lebih dari satu per item)
        Schema::create('po_item_penerima', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_item_id');
            $table->string('nama');
            $table->timestamps();

            $table->foreign('po_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_item_penerima');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
