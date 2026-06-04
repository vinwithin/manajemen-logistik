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
        Schema::create('transfer_pakan_header', function (Blueprint $table) {
            $table->id();
            $table->string('no_transfer', 100)->unique();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->date('tanggal_transfer');
            $table->foreignId('tujuan_id')->nullable()->constrained('tujuan')->nullOnDelete();
            $table->foreignId('pengirim_id')->nullable()->constrained('penerima')->nullOnDelete();
            $table->string('nama_pengirim', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pakan_header');
    }
};
