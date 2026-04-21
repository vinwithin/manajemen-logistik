<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangLansir extends Model
{
    protected $table = 'gudang_lansir';

    protected $fillable = [
        'tujuan_id',
        'kode_pakan_id',
        'jumlah_kg',
        'jumlah_karung',
        'ongkos_per_kg',
        'upah_per_kg',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'ongkos_per_kg' => 'decimal:2',
        'upah_per_kg' => 'decimal:2',
        'jumlah_karung' => 'integer',
    ];

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }

    public function mobils(): HasMany
    {
        return $this->hasMany(GudangLansirMobil::class, 'lansir_id');
    }

    public function tims(): HasMany
    {
        return $this->hasMany(GudangLansirTim::class, 'lansir_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
