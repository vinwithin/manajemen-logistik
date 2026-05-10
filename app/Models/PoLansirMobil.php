<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PoLansirMobil extends Model
{
    protected $table = 'po_lansir_mobil';

    protected $fillable = ['lansir_id', 'no_polisi', 'nama_sopir', 'berat', 'jumlah_karung', 'ongkos', 'keterangan'];

    public function lansir()
    {
        return $this->belongsTo(PoItemLansir::class, 'lansir_id');
    }

    public function getTotalOngkosAttribute(): float
    {
        return ($this->berat ?? 0) * ($this->ongkos ?? 0);
    }

    /** Semua riwayat assignment GPS */
    public function gpsAssignments(): MorphMany
    {
        return $this->morphMany(GpsAssignment::class, 'assignable');
    }

    /** Assignment GPS yang sedang aktif */
    public function activeGps(): MorphOne
    {
        return $this->morphOne(GpsAssignment::class, 'assignable')->whereNull('unassigned_at');
    }
}
