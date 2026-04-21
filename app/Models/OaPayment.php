<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OaPayment extends Model
{
    protected $table = 'oa_payments';

    protected $fillable = [
        'po_item_id', 'po_penerima_id', 'supplier_id', 'jumlah_tagihan',
        'jumlah_bayar', 'tanggal_bayar', 'metode_bayar',
        'bukti_bayar', 'keterangan', 'status',
    ];

    protected $casts = ['tanggal_bayar' => 'date'];

    const METODE = [
        'transfer' => 'Transfer Bank',
        'tunai'    => 'Tunai',
        'cek'      => 'Cek / Giro',
    ];

    const STATUS = [
        'pending' => 'Belum Bayar',
        'partial' => 'Bayar Sebagian',
        'lunas'   => 'Lunas',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function getSisaTagihanAttribute(): float
    {
        return max(0, $this->jumlah_tagihan - $this->jumlah_bayar);
    }
}
