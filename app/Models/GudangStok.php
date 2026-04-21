<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangStok extends Model
{
    protected $table = 'gudang_stok';

    protected $fillable = [
        'tujuan_id',
        'kode_pakan_id',
        'stok_kg',
        'stok_karung',
    ];

    protected $casts = [
        'stok_kg' => 'decimal:2',
        'stok_karung' => 'integer',
    ];

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }
}
