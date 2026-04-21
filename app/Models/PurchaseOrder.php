<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    protected $fillable = ['no_po', 'tanggal_po', 'cv_id', 'status', 'catatan'];

    protected $casts = ['tanggal_po' => 'date'];

    const STATUS_DRAFT = 'draft';

    const STATUS_LOCKED = 'locked';

    public function cv()
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function kendaraans(): HasMany
    {
        return $this->hasMany(PoKendaraan::class, 'po_id');
    }

    // Bisa dikunci jika semua kendaraan sudah selesai atau batal
    public function canLock(): bool
    {
        return $this->kendaraans->count() > 0
            && $this->kendaraans->whereNotIn('status', ['selesai', 'batal', 'berangkat'])->count() === 0;
    }

    public function lansirPayments(): HasMany
    {
        return $this->hasMany(LansirPayment::class, 'po_id');
    }

    public function lansirPaymentMobil(): HasOne
    {
        return $this->hasOne(LansirPayment::class, 'po_id')->where('tipe', LansirPayment::TIPE_MOBIL);
    }

    public function lansirPaymentTim(): HasOne
    {
        return $this->hasOne(LansirPayment::class, 'po_id')->where('tipe', LansirPayment::TIPE_TIM);
    }
}
