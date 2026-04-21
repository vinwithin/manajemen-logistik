<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_penerima_pakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_penerima_id')->constrained('po_penerima')->cascadeOnDelete();
            $table->foreignId('kode_pakan_id')->constrained('kode_pakan')->restrictOnDelete();
            $table->decimal('jumlah_kg', 10, 2);
            $table->integer('jumlah_karung')->comment('ceil(jumlah_kg / 50)');
            $table->decimal('ongkos_oa', 15, 2)->default(0)->comment('Rp/kg ke supplier');
            $table->decimal('harga_pt_sum', 15, 2)->default(0)->comment('Rp/kg ke PT SUM');
            $table->timestamps();

            $table->unique(['po_penerima_id', 'kode_pakan_id'], 'uq_penerima_pakan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_penerima_pakan');
    }
};
