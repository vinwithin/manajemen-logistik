<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rugi_laba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->onDelete('cascade');
            $table->integer('bulan');   // 1-12
            $table->integer('tahun');
            $table->string('periode_label')->nullable(); // "April 2026"

            // Biaya Operasional Manual
            $table->decimal('gaji',                    15, 2)->default(0);
            $table->decimal('atk',                     15, 2)->default(0);
            $table->decimal('pembayaran_supplier_lintas', 15, 2)->default(0);
            $table->decimal('pembayaran_mobil_lokal',  15, 2)->default(0);
            $table->decimal('sharing_fee',             15, 2)->default(0);
            $table->decimal('sharing_profit',          15, 2)->default(0);
            $table->decimal('perjalanan_dinas',        15, 2)->default(0);
            $table->decimal('entertain',               15, 2)->default(0);
            $table->decimal('adm_bank',                15, 2)->default(0);
            $table->decimal('upah_bongkar',            15, 2)->default(0);
            $table->decimal('upah_muat',               15, 2)->default(0);
            $table->decimal('upah_bongkar_muat',       15, 2)->default(0);
            $table->decimal('biaya_lain_lain',         15, 2)->default(0);
            $table->decimal('bbm',                     15, 2)->default(0);
            $table->decimal('listrik',                 15, 2)->default(0);
            $table->decimal('pdam',                    15, 2)->default(0);
            $table->decimal('potongan_voucher',        15, 2)->default(0);

            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cv_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rugi_laba');
    }
};
