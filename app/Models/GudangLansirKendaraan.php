<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangLansirKendaraan extends Model
{
    protected $table = 'gudang_lansir_kendaraan';

    protected $fillable = [
        'gudang_id',
        'no_polisi',
        'nama_sopir',
        'no_surat_jalan',
        'tanggal_lansir',
        'total_kg',
        'total_karung',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_lansir' => 'date',
        'total_kg' => 'decimal:2',
        'total_karung' => 'integer',
    ];

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'gudang_id');
    }

    public function penerimas(): HasMany
    {
        return $this->hasMany(GudangLansirPenerima::class, 'kendaraan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Hitung total KG dari semua penerima
    public function getTotalKgAttribute(): float
    {
        return (float) $this->penerimas->flatMap->pakans->sum('jumlah_kg');
    }

    // Hitung total karung dari semua penerima
    public function getTotalKarungAttribute(): int
    {
        return (int) $this->penerimas->flatMap->pakans->sum('jumlah_karung');
    }
}
