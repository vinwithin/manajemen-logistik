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
        Schema::create('transfer_pakan_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_id')->constrained('transfer_pakan_header')->cascadeOnDelete();
            $table->string('no_polisi', 20);
            $table->string('nama_sopir')->nullable();
            $table->string('no_surat_jalan', 100)->nullable();
            $table->decimal('total_kg', 10, 2)->default(0);
            $table->integer('total_karung')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pakan_kendaraan');
    }
};
