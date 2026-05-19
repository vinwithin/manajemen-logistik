<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PoPenerima extends Model
{
    protected $table = 'po_penerima';

    protected $fillable = [
        'po_kendaraan_id', 'penerima_id', 'nama_penerima', 'no_do', 'tujuan_id', 'status',
        'bukti_tiba', 'validasi_oleh', 'tiba_at',
    ];

    protected $casts = [
        'tiba_at' => 'datetime',
    ];

    const STATUSES = ['pending', 'berangkat', 'tiba', 'selesai', 'batal'];

    const VALID_TRANSITIONS = [
        'pending'   => ['berangkat', 'batal'],
        'berangkat' => ['tiba', 'batal'],
        'tiba'      => ['selesai'],
        'selesai'   => [],
        'batal'     => [],
    ];

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(PoKendaraan::class, 'po_kendaraan_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Penerima::class, 'penerima_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function pakans(): HasMany
    {
        return $this->hasMany(PoPenerimaPakan::class, 'po_penerima_id');
    }

    public function oaPayment(): HasOne
    {
        return $this->hasOne(OaPayment::class, 'po_penerima_id');
    }

    public function lansirs(): HasMany
    {
        return $this->hasMany(PoPenerimaLansir::class, 'po_penerima_id');
    }

    public function gudangLansirs(): HasMany
    {
        return $this->hasMany(GudangLansirPenerima::class, 'po_penerima_id');
    }

    // Total KG semua pakan penerima ini
    public function getTotalKgAttribute(): float
    {
        return (float) $this->pakans->sum('jumlah_kg');
    }

    // Total OA = sum(jumlah_kg × ongkos_oa) per baris pakan
    public function getTotalOaAttribute(): float
    {
        return (float) $this->pakans->sum(fn($p) => $p->jumlah_kg * $p->ongkos_oa);
    }

    // Total PT SUM = sum(jumlah_kg × harga_pt_sum) per baris pakan
    public function getTotalPtSumAttribute(): float
    {
        return (float) $this->pakans->sum(fn($p) => $p->jumlah_kg * $p->harga_pt_sum);
    }
}
