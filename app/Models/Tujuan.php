<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tujuan extends Model
{
    protected $table = 'tujuan';

    protected $fillable = ['type', 'nama', 'is_aktif', 'lat', 'lng', 'geofence_radius', 'idtrack_marker_id'];

    protected $casts = [
        'is_aktif'        => 'boolean',
        'lat'             => 'decimal:7',
        'lng'             => 'decimal:7',
        'geofence_radius' => 'integer',
    ];

    /**
     * Cek apakah koordinat GPS sudah masuk dalam radius geofence.
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
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    const TYPES = [
        'direct'     => 'Direct',
        'gudang'     => 'Gudang',
        'co_farm'    => 'Co Farm',
        'rent_farm'  => 'Rent Farm',
    ];

    public function cv()
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function stoks()
    {
        return $this->hasMany(GudangStok::class, 'tujuan_id');
    }

    /**
     * Relasi many-to-many dengan Supplier
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_tujuan')
            ->withPivot('ongkos_angkut', 'jenis_kendaraan')
            ->withTimestamps();
    }

    public function userTujuan()
    {
        return $this->hasMany(UserTujuan::class);
    }
}
