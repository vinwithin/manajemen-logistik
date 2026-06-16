<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('po_kendaraan', 'no_surat_jalan') && ! Schema::hasColumn('po_kendaraan', 'no_hp')) {
            Schema::table('po_kendaraan', function (Blueprint $table) {
                $table->renameColumn('no_surat_jalan', 'no_hp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('po_kendaraan', 'no_hp') && ! Schema::hasColumn('po_kendaraan', 'no_surat_jalan')) {
            Schema::table('po_kendaraan', function (Blueprint $table) {
                $table->renameColumn('no_hp', 'no_surat_jalan');
            });
        }
    }
};
