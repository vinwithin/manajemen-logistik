<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_lansir_header', function (Blueprint $table) {
            // Tambah nullable dulu agar aman jika ada data lama
            $table->foreignId('cv_id')->nullable()->after('gudang_id')
                ->constrained('cv')->nullOnDelete()
                ->comment('CV yang bertanggung jawab atas lansir ini');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_lansir_header', function (Blueprint $table) {
            $table->dropForeign(['cv_id']);
            $table->dropColumn('cv_id');
        });
    }
};
