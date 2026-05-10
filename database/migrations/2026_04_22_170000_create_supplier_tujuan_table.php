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
        Schema::create('supplier_tujuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('tujuan_id')->constrained('tujuan')->onDelete('cascade');
            $table->string('jenis_kendaraan')->nullable(); // Tronton, Colt Diesel, dll
            $table->decimal('ongkos_angkut', 15, 2)->default(0);
            $table->timestamps();

            // Unique constraint untuk mencegah duplikasi
            $table->unique(['supplier_id', 'tujuan_id', 'jenis_kendaraan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_tujuan');
    }
};
