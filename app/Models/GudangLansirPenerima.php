<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangLansirPenerima extends Model
{
    protected $table = 'gudang_lansir_penerima';

    protected $fillable = [
        'kendaraan_id',
        'po_penerima_id',
        'nama_penerima',
        'tujuan_id',
        'status',
        'bukti_tiba',
        'tiba_at',
        'validasi_oleh',
    ];

    protected $casts = [
        'tiba_at' => 'datetime',
    ];

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(GudangLansirKendaraan::class, 'kendaraan_id');
    }

    public function poPenerima(): BelongsTo
    {
        return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function pakans(): HasMany
    {
        return $this->hasMany(GudangLansirPakan::class, 'penerima_id');
    }

    public function tims(): HasMany
    {
        return $this->hasMany(GudangLansirTim::class, 'penerima_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validasi_oleh');
    }

    // Total KG penerima ini
    public function getTotalKgAttribute(): float
    {
        return (float) $this->pakans->sum('jumlah_kg');
    }

    // Total karung penerima ini
    public function getTotalKarungAttribute(): int
    {
        return (int) $this->pakans->sum('jumlah_karung');
    }

    // Total ongkos angkut
    public function getTotalOaAttribute(): float
    {
        return (float) $this->pakans->sum(fn($p) => $p->jumlah_kg * ($p->ongkos_oa ?? 0));
    }
}
