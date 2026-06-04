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
        Schema::create('transfer_pakan_tim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerima_id')->constrained('transfer_pakan_penerima')->cascadeOnDelete();
            $table->string('nama_tim', 255);
            $table->decimal('jumlah_kg', 10, 2)->default(0);
            $table->integer('jumlah_karung')->default(0);
            $table->decimal('upah_per_kg', 15, 2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pakan_tim');
    }
};
