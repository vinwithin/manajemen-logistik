<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_lansir_pakan', function (Blueprint $table) {
            $table->decimal('harga_pt_sum', 15, 2)->nullable()->default(0)
                ->after('ongkos_oa')
                ->comment('Harga PT Sum per kg untuk mobil lansir');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_lansir_pakan', function (Blueprint $table) {
            $table->dropColumn('harga_pt_sum');
        });
    }
};
