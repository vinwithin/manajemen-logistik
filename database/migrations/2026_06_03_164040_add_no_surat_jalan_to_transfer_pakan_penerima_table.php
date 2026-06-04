<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_pakan_penerima', function (Blueprint $table) {
            $table->string('no_surat_jalan', 100)->nullable()->after('nama_penerima');
        });

        // Hapus no_surat_jalan dari kendaraan jika ada
        if (Schema::hasColumn('transfer_pakan_kendaraan', 'no_surat_jalan')) {
            Schema::table('transfer_pakan_kendaraan', function (Blueprint $table) {
                $table->dropColumn('no_surat_jalan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('transfer_pakan_penerima', function (Blueprint $table) {
            $table->dropColumn('no_surat_jalan');
        });

        Schema::table('transfer_pakan_kendaraan', function (Blueprint $table) {
            $table->string('no_surat_jalan', 100)->nullable()->after('nama_sopir');
        });
    }
};
