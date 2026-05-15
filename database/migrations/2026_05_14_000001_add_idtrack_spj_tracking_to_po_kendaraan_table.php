<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_kendaraan', function (Blueprint $table) {
            $table->timestamp('idtrack_spj_sent_at')->nullable();
            $table->string('idtrack_spj_nomor_surat', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('po_kendaraan', function (Blueprint $table) {
            $table->dropColumn(['idtrack_spj_sent_at', 'idtrack_spj_nomor_surat']);
        });
    }
};
