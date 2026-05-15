<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penerima extends Model
{
    protected $table = 'penerima';

    protected $fillable = [
        'nama',
        'tujuan_id',
        'ongkos_angkut',
        'ongkos_bongkar',
        'alamat',
        'telepon',
        'is_aktif',
        'lat',
        'lng',
        'geofence_radius',
        'idtrack_marker_id',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'ongkos_angkut' => 'decimal:2',
        'ongkos_bongkar' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'geofence_radius' => 'integer',
        'idtrack_marker_id' => 'integer',
    ];

    /**
     * Cek apakah koordinat GPS sudah masuk dalam radius geofence.
     * Menggunakan formula Haversine.
     */
    public function isInsideGeofence(float $gpsLat, float $gpsLng): bool
    {
        if (! $this->lat || ! $this->lng) {
            return false;
        }

        return $this->haversineDistance($this->lat, $this->lng, $gpsLat, $gpsLng)
            <= ($this->geofence_radius ?? 500);
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }
}
