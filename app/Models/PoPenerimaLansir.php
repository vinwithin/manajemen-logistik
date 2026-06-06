<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoPenerimaLansir extends Model
{
    protected $table = 'po_penerima_lansir';

    protected $fillable = ['po_penerima_id', 'no_do', 'validasi_oleh', 'tanggal_lansir', 'selesai_at'];

    protected $casts = [
        'selesai_at'     => 'datetime',
        'tanggal_lansir' => 'date',
    ];

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
    }

    public function mobils(): HasMany
    {
        return $this->hasMany(PoLansirMobil::class, 'lansir_id');
    }

    public function tims(): HasMany
    {
        return $this->hasMany(PoLansirTim::class, 'lansir_id');
    }

    public function getTotalBeratAttribute(): float
    {
        return (float) $this->mobils->sum('berat');
    }

    public function getTotalOngkosAttribute(): float
    {
        return (float) $this->mobils->sum(fn($m) => ($m->berat ?? 0) * ($m->ongkos ?? 0));
    }

    public function getTotalUpahAttribute(): float
    {
        return (float) $this->tims->sum(fn($t) => ($t->berat ?? 0) * ($t->upah ?? 0));
    }
}
