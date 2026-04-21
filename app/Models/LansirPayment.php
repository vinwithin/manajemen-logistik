<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LansirPayment extends Model
{
    protected $table = 'lansir_payments';

    protected $fillable = ['po_id', 'tipe', 'status', 'tanggal_bayar', 'catatan', 'dibayar_oleh'];

    protected $casts = ['tanggal_bayar' => 'date'];

    const TIPE_MOBIL = 'mobil';
    const TIPE_TIM   = 'tim';
    const STATUS_BELUM = 'belum_bayar';
    const STATUS_SUDAH = 'sudah_bayar';

    public function po(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function isSudahBayar(): bool
    {
        return $this->status === self::STATUS_SUDAH;
    }
}
