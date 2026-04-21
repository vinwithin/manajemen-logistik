<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_lansir_penerima', function (Blueprint $table) {
            $table->enum('status', ['dalam_perjalanan', 'tiba', 'selesai'])
                ->default('dalam_perjalanan')
                ->after('tujuan_id')
                ->comment('Status pengiriman ke penerima');

            $table->string('bukti_tiba')->nullable()->after('status')->comment('Path file bukti tiba');
            $table->timestamp('tiba_at')->nullable()->after('bukti_tiba')->comment('Waktu tiba di penerima');
            $table->foreignId('validasi_oleh')->nullable()->after('tiba_at')->constrained('users')->comment('User yang validasi tiba');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_lansir_penerima', function (Blueprint $table) {
            $table->dropForeign(['validasi_oleh']);
            $table->dropColumn(['status', 'bukti_tiba', 'tiba_at', 'validasi_oleh']);
        });
    }
};
