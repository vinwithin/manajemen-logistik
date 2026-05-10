<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GpsAssignment extends Model
{
    protected $table = 'gps_assignments';

    protected $fillable = [
        'device_id',
        'device_name',
        'assignable_type',
        'assignable_id',
        'assigned_at',
        'unassigned_at',
        'catatan',
    ];

    protected $casts = [
        'assigned_at'   => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    /** Relasi polymorphic ke PoKendaraan atau PoLansirMobil */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Scope: hanya assignment yang masih aktif (belum di-unassign) */
    public function scopeActive($query)
    {
        return $query->whereNull('unassigned_at');
    }

    public function isActive(): bool
    {
        return $this->unassigned_at === null;
    }
}
