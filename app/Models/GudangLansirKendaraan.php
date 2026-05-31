<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangLansirKendaraan extends Model
{
    protected $table = 'gudang_lansir_kendaraan';

    protected $fillable = [
        'lansir_header_id',
        'no_polisi',
        'nama_sopir',
        'total_kg',
        'total_karung',
        'created_by',
    ];

    protected $casts = [
        'total_kg' => 'decimal:2',
        'total_karung' => 'integer',
    ];

    public function lansirHeader(): BelongsTo
    {
        return $this->belongsTo(GudangLansirHeader::class, 'lansir_header_id');
    }

    public function gudang(): BelongsTo
    {
        // Akses gudang melalui header
        return $this->hasOneThrough(
            Tujuan::class,
            GudangLansirHeader::class,
            'id', // Foreign key on gudang_lansir_header
            'id', // Foreign key on tujuan
            'lansir_header_id', // Local key on gudang_lansir_kendaraan
            'gudang_id' // Local key on gudang_lansir_header
        );
    }

    public function penerimas(): HasMany
    {
        return $this->hasMany(GudangLansirPenerima::class, 'kendaraan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
