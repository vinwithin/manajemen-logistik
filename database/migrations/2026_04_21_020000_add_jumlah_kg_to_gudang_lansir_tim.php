<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_lansir_tim', function (Blueprint $table) {
            $table->decimal('jumlah_kg', 12, 2)->after('nama_tim')->comment('Jumlah kg yang dibongkar tim ini');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_lansir_tim', function (Blueprint $table) {
            $table->dropColumn('jumlah_kg');
        });
    }
};
