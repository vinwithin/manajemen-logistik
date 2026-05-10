<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Level 0: Lansir Header (parent utama)
        Schema::create('gudang_lansir_header', function (Blueprint $table) {
            $table->id();
            $table->string('no_lansir', 50)->unique()->comment('Nomor lansir unik');
            $table->foreignId('gudang_id')->constrained('tujuan')->comment('Gudang asal');
            $table->date('tanggal_lansir');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Update kendaraan: tambah lansir_header_id
        Schema::table('gudang_lansir_kendaraan', function (Blueprint $table) {
            $table->foreignId('lansir_header_id')->nullable()->after('id')->constrained('gudang_lansir_header')->onDelete('cascade');
            $table->dropForeign(['gudang_id']);
            $table->dropColumn('gudang_id');
            $table->dropColumn('tanggal_lansir');
            $table->dropColumn('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_lansir_kendaraan', function (Blueprint $table) {
            $table->dropForeign(['lansir_header_id']);
            $table->dropColumn('lansir_header_id');
            $table->foreignId('gudang_id')->after('id')->constrained('tujuan');
            $table->date('tanggal_lansir')->after('nama_sopir');
            $table->text('catatan')->nullable()->after('total_karung');
        });

        Schema::dropIfExists('gudang_lansir_header');
    }
};
