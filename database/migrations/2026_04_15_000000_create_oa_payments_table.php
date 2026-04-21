<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oa_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_item_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('jumlah_tagihan', 15, 2)->default(0)->comment('berat × ongkos');
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->date('tanggal_bayar')->nullable();
            $table->enum('metode_bayar', ['transfer', 'tunai', 'cek'])->nullable();
            $table->string('bukti_bayar')->nullable()->comment('path file');
            $table->string('keterangan')->nullable();
            $table->enum('status', ['pending', 'partial', 'lunas'])->default('pending');
            $table->timestamps();

            $table->foreign('po_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oa_payments');
    }
};
