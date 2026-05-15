<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoKendaraanIdtrackMarkerVisit extends Model
{
    protected $table = 'po_kendaraan_idtrack_marker_visits';

    protected $fillable = [
        'po_kendaraan_id',
        'idtrack_marker_id',
        'arrived_at',
        'lat',
        'lng',
        'marker_name',
        'callback_hash',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(PoKendaraan::class, 'po_kendaraan_id');
    }
}
