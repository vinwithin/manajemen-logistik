<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_kendaraan_idtrack_marker_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_kendaraan_id')->constrained('po_kendaraan')->cascadeOnDelete();
            $table->unsignedInteger('idtrack_marker_id');
            $table->timestamp('arrived_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('marker_name')->nullable();
            $table->string('callback_hash', 64)->unique();
            $table->timestamps();

            $table->index(['po_kendaraan_id', 'arrived_at'], 'pk_idtrack_visits_k_arrived');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_kendaraan_idtrack_marker_visits');
    }
};
