<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oa_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('po_penerima_id')->nullable()->after('po_item_id');
            $table->foreign('po_penerima_id')->references('id')->on('po_penerima')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oa_payments', function (Blueprint $table) {
            $table->dropForeign(['po_penerima_id']);
            $table->dropColumn('po_penerima_id');
        });
    }
};
