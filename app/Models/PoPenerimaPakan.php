<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoPenerimaPakan extends Model
{
    protected $table = 'po_penerima_pakan';

    protected $fillable = [
        'po_penerima_id', 'kode_pakan_id', 'jumlah_kg', 'jumlah_karung', 'ongkos_oa', 'harga_pt_sum',
    ];

    protected $casts = [
        'ongkos_oa'    => 'decimal:2',
        'harga_pt_sum' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $model->jumlah_karung = (int) ceil($model->jumlah_kg / 50);
        });
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }

    // Total OA baris ini
    public function getTotalOaAttribute(): float
    {
        return (float) $this->jumlah_kg * (float) $this->ongkos_oa;
    }

    // Total PT SUM baris ini
    public function getTotalPtSumAttribute(): float
    {
        return (float) $this->jumlah_kg * (float) $this->harga_pt_sum;
    }
}
