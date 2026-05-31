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
        Schema::table('gudang_lansir_penerima', function (Blueprint $table) {
            $table->string('no_surat_jalan', 100)->nullable()->after('tujuan_id');
        });

     
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
