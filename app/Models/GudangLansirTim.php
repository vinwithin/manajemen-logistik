<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangLansirTim extends Model
{
    protected $table = 'gudang_lansir_tim';

    protected $fillable = [
        'penerima_id',
        'nama_tim',
        'jumlah_kg',
        'upah_per_kg',
        'keterangan',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'upah_per_kg' => 'decimal:2',
    ];

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(GudangLansirPenerima::class, 'penerima_id');
    }

    // Total upah tim ini
    public function getTotalUpahAttribute(): float
    {
        return (float) ($this->jumlah_kg ?? 0) * ($this->upah_per_kg ?? 0);
    }
}
