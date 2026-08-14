<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferPakanHeader extends Model
{
    protected $table = 'transfer_pakan_header';

    protected $fillable = [
        'no_transfer',
        'cv_id',
        'tanggal_transfer',
        'tujuan_id',
        'pengirim_id',
        'nama_pengirim',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_transfer' => 'date',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(Penerima::class, 'pengirim_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kendaraans(): HasMany
    {
        return $this->hasMany(TransferPakanKendaraan::class, 'header_id');
    }

    public function lansirPayments(): HasMany
    {
        return $this->hasMany(LansirPayment::class, 'transfer_pakan_header_id');
    }

    public function getTotalKgAttribute(): float
    {
        return (float) $this->kendaraans->sum('total_kg');
    }

    public function getTotalKarungAttribute(): int
    {
        return (int) $this->kendaraans->sum('total_karung');
    }

    public function getTotalPtSumAttribute(): float
    {
        return (float) $this->kendaraans
            ->flatMap->penerimas
            ->flatMap->pakans
            ->sum(fn ($p) => $p->jumlah_kg * $p->harga_pt_sum);
    }
}
