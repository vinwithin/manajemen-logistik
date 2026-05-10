<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('no_dokumen_prefix')->comment('Path file logo perusahaan');
        });
    }

    public function down(): void
    {
        Schema::table('cv', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
