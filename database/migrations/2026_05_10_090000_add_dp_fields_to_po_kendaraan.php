<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_kendaraan', function (Blueprint $table) {
            $table->decimal('dp_nominal', 15, 2)->default(0)->after('jumlah_karung')
                ->comment('Nominal DP yang dibayarkan ke supplier');
            $table->decimal('dp_persen', 5, 2)->nullable()->after('dp_nominal')
                ->comment('Persentase DP dari total tagihan (auto-calculated)');
            $table->date('dp_tanggal')->nullable()->after('dp_persen')
                ->comment('Tanggal pembayaran DP');
            $table->string('dp_metode', 50)->nullable()->after('dp_tanggal')
                ->comment('Metode pembayaran: transfer, tunai, giro');
            $table->text('dp_keterangan')->nullable()->after('dp_metode')
                ->comment('Catatan pembayaran DP');
        });
    }

    public function down(): void
    {
        Schema::table('po_kendaraan', function (Blueprint $table) {
            $table->dropColumn(['dp_nominal', 'dp_persen', 'dp_tanggal', 'dp_metode', 'dp_keterangan']);
        });
    }
};
