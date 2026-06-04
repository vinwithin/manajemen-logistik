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
        Schema::create('transfer_pakan_pakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerima_id')->constrained('transfer_pakan_penerima')->cascadeOnDelete();
            $table->foreignId('kode_pakan_id')->constrained('kode_pakan')->cascadeOnDelete();
            $table->decimal('jumlah_kg', 10, 2);
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('ongkos_oa', 15, 2)->default(0);
            $table->decimal('harga_pt_sum', 15, 2)->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pakan_pakan');
    }
};
