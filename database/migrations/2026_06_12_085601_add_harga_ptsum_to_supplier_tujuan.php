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
        Schema::table('supplier_tujuan', function (Blueprint $table) {
            $table->decimal('harga_pt_sum', 15, 2)->default(0)->after('ongkos_angkut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_tujuan', function (Blueprint $table) {
            $table->dropColumn('harga_pt_sum');
        });
    }
};
