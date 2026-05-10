<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('po_item_lansir', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_item_id');
            $table->string('no_polisi_lansir')->comment('Plat nomor mobil lansir');
            $table->string('tim_bongkar')->nullable()->comment('Nama/keterangan tim bongkar');
            $table->decimal('berat_lansir', 10, 2)->nullable()->comment('kg yang dilansir');
            $table->decimal('ongkos_lansir', 15, 2)->nullable()->comment('Rp/kg ongkos angkut lansir');
            $table->decimal('upah_bongkar', 15, 2)->nullable()->comment('Rp/kg upah tim bongkar');
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            $table->foreign('po_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_item_lansir');
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['tiba_at', 'validasi_oleh']);
        });
    }
};
