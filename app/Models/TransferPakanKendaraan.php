<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferPakanKendaraan extends Model
{
    protected $table = 'transfer_pakan_kendaraan';

    protected $fillable = [
        'header_id',
        'no_polisi',
        'nama_sopir',
        'total_kg',
        'total_karung',
    ];

    protected $casts = [
        'total_kg' => 'decimal:2',
        'total_karung' => 'integer',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(TransferPakanHeader::class, 'header_id');
    }

    public function penerimas(): HasMany
    {
        return $this->hasMany(TransferPakanPenerima::class, 'kendaraan_id');
    }
}
