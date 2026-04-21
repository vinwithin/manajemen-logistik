<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_penerima_lansir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_penerima_id')->constrained('po_penerima')->cascadeOnDelete();
            $table->string('validasi_oleh');
            $table->date('tanggal_lansir')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
        });

        Schema::table('po_lansir_mobil', function (Blueprint $table) {
            $table->dropForeign(['lansir_id']);
            $table->foreign('lansir_id')
                ->references('id')
                ->on('po_penerima_lansir') 
                ->cascadeOnDelete();
        });

        Schema::table('po_lansir_tim', function (Blueprint $table) {
            $table->dropForeign(['lansir_id']);
            $table->foreign('lansir_id')
                ->references('id')
                ->on('po_penerima_lansir') 
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
      
    }
};
