<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lansir_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_id');
            $table->enum('tipe', ['mobil', 'tim']);
            $table->enum('status', ['belum_bayar', 'sudah_bayar'])->default('belum_bayar');
            $table->date('tanggal_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->string('dibayar_oleh')->nullable();
            $table->timestamps();
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->unique(['po_id', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lansir_payments');
    }
};
