<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_lansir_pakan', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->after('ongkos_oa');
        });

        Schema::table('gudang_lansir_tim', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->after('upah_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_lansir_pakan', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });

        Schema::table('gudang_lansir_tim', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
