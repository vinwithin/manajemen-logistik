<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_kendaraan', function (Blueprint $table) {
            $table->decimal('ongkos_angkut', 15, 2)->default(0)->after('tujuan_id')
                ->comment('Ongkos angkut per kg dari supplier ke tujuan');
        });
    }

    public function down(): void
    {
        Schema::table('po_kendaraan', function (Blueprint $table) {
            $table->dropColumn('ongkos_angkut');
        });
    }
};
