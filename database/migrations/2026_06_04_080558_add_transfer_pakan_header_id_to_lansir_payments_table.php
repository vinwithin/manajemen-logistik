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
        Schema::table('lansir_payments', function (Blueprint $table) {
            $table->foreignId('transfer_pakan_header_id')->nullable()->constrained('transfer_pakan_header')->cascadeOnDelete()->after('gudang_lansir_header_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lansir_payments', function (Blueprint $table) {
            $table->dropForeign(['transfer_pakan_header_id']);
            $table->dropColumn('transfer_pakan_header_id');
        });
    }
};
