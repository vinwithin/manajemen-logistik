<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangLansirPakan extends Model
{
    protected $table = 'gudang_lansir_pakan';

    protected $fillable = [
        'penerima_id',
        'kode_pakan_id',
        'jumlah_kg',
        'jumlah_karung',
        'ongkos_oa',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'jumlah_karung' => 'integer',
        'ongkos_oa' => 'decimal:2',
    ];

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(GudangLansirPenerima::class, 'penerima_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }
}
