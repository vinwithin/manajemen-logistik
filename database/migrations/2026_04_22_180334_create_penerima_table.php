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
        Schema::create('penerima', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Nama penerima/peternak
            $table->foreignId('tujuan_id')->constrained('tujuan')->onDelete('cascade');
            $table->decimal('ongkos_angkut', 15, 2)->default(0); // Ongkos untuk lansir mobil
            $table->decimal('ongkos_bongkar', 15, 2)->default(0);
            $table->text('alamat')->nullable();
            $table->decimal('lat', 10, 7)->nullable()->comment('Latitude lokasi kandang');
            $table->decimal('lng', 10, 7)->nullable()->comment('Longitude lokasi kandang');
            $table->unsignedInteger('geofence_radius')->default(500)->comment('Radius geofence dalam meter');
            $table->unsignedInteger('idtrack_marker_id')->nullable()->comment('IDMarker dari Idtrack API');
            $table->string('telepon', 20)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            // Index untuk pencarian
            $table->index('nama');
            $table->index('tujuan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerima');
    }
};
