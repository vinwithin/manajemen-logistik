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
        Schema::create('tujuan', function (Blueprint $table) {
            $table->id();
            $table->integer('cv_id');
            $table->string('type')->nullable();
            $table->string('nama');
            $table->decimal('lat', 10, 7)->nullable()->comment('Latitude lokasi tujuan');
            $table->decimal('lng', 10, 7)->nullable()->comment('Longitude lokasi tujuan');
            $table->unsignedInteger('geofence_radius')->default(500)->comment('Radius geofence dalam meter');
            $table->unsignedInteger('idtrack_marker_id')->nullable()->comment('IDMarker dari Idtrack API');
            $table->boolean('is_aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
