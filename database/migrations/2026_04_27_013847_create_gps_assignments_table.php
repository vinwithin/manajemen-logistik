<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_assignments', function (Blueprint $table) {
            $table->id();
            // Device ID dari Idtrack
            $table->unsignedInteger('device_id');
            $table->string('device_name')->nullable()->comment('Nama/label device dari Idtrack');

            // Polymorphic: bisa PoKendaraan atau PoLansirMobil
            $table->morphs('assignable'); // assignable_type + assignable_id

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable()->comment('Null = masih aktif');
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'unassigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_assignments');
    }
};
