<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangMutasiStok extends Model
{
    protected $table = 'gudang_mutasi_stok';

    protected $fillable = [
        'tujuan_id',
        'kode_pakan_id',
        'tipe',
        'jumlah_kg',
        'jumlah_karung',
        'referensi_tipe',
        'referensi_id',
        'po_penerima_id',
        'saldo_kg_after',
        'saldo_karung_after',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'saldo_kg_after' => 'decimal:2',
        'jumlah_karung' => 'integer',
        'saldo_karung_after' => 'integer',
    ];

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }

    public function poPenerima(): BelongsTo
    {
        return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
    }
}
