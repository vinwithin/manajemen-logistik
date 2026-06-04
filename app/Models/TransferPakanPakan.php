<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferPakanPakan extends Model
{
    protected $table = 'transfer_pakan_pakan';

    protected $fillable = [
        'penerima_id',
        'kode_pakan_id',
        'jumlah_kg',
        'jumlah_karung',
        'ongkos_oa',
        'harga_pt_sum',
        'keterangan',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'ongkos_oa' => 'decimal:2',
        'harga_pt_sum' => 'decimal:2',
    ];

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(TransferPakanPenerima::class, 'penerima_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }

    public function getTotalOaAttribute(): float
    {
        return (float) $this->jumlah_kg * (float) $this->ongkos_oa;
    }

    public function getTotalPtSumAttribute(): float
    {
        return (float) $this->jumlah_kg * (float) $this->harga_pt_sum;
    }
}
