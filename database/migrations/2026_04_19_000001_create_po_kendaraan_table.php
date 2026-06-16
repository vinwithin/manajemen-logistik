<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('no_polisi', 20);
            $table->string('nama_sopir')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->integer('tujuan_id')->nullable();
            $table->string('jenis_kendaraan')->nullable();
            $table->decimal('jumlah_kg', 10, 2)->nullable();
            $table->integer('jumlah_karung')->nullable()->comment('ceil(jumlah_kg / 50)');
            $table->enum('status', ['pending', 'berangkat', 'selesai', 'batal'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_kendaraan');
    }
};
