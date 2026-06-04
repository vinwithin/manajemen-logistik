<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferPakanTim extends Model
{
    protected $table = 'transfer_pakan_tim';

    protected $fillable = [
        'penerima_id',
        'nama_tim',
        'jumlah_kg',
        'jumlah_karung',
        'upah_per_kg',
        'keterangan',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'upah_per_kg' => 'decimal:2',
    ];

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(TransferPakanPenerima::class, 'penerima_id');
    }

    public function getTotalUpahAttribute(): float
    {
        return (float) $this->jumlah_kg * (float) ($this->upah_per_kg ?? 0);
    }
}
