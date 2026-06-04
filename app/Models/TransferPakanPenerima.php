<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferPakanPenerima extends Model
{
    protected $table = 'transfer_pakan_penerima';

    protected $fillable = [
        'kendaraan_id',
        'penerima_id',
        'nama_penerima',
        'no_surat_jalan',
        'tujuan_id',
        'status',
        'bukti_tiba',
        'tiba_at',
        'validasi_oleh',
    ];

    protected $casts = [
        'tiba_at' => 'datetime',
    ];

    const STATUSES = ['pending', 'tiba', 'selesai'];

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(TransferPakanKendaraan::class, 'kendaraan_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Penerima::class, 'penerima_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function pakans(): HasMany
    {
        return $this->hasMany(TransferPakanPakan::class, 'penerima_id');
    }

    public function tims(): HasMany
    {
        return $this->hasMany(TransferPakanTim::class, 'penerima_id');
    }

    public function getTotalKgAttribute(): float
    {
        return (float) $this->pakans->sum('jumlah_kg');
    }

    public function getTotalKarungAttribute(): int
    {
        return (int) $this->pakans->sum('jumlah_karung');
    }

    public function getTotalOaAttribute(): float
    {
        return (float) $this->pakans->sum(fn ($p) => $p->jumlah_kg * $p->ongkos_oa);
    }

    public function getTotalPtSumAttribute(): float
    {
        return (float) $this->pakans->sum(fn ($p) => $p->jumlah_kg * $p->harga_pt_sum);
    }
}
