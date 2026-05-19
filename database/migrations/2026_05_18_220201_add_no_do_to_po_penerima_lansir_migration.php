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
       Schema::table('po_penerima_lansir', function (Blueprint $table) {
            $table->string('no_do')
                ->nullable()
                ->after('po_penerima_id')
                ->comment('surat jalan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_penerima_lansir', function (Blueprint $table) {
            //
        });
    }
};
