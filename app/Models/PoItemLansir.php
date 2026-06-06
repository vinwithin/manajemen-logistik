<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoItemLansir extends Model
{
    protected $table = 'po_item_lansir';

    protected $fillable = ['po_item_id', 'validasi_oleh', 'selesai_at'];

    protected $casts = ['selesai_at' => 'datetime'];

    public function item()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }

    public function mobils()
    {
        return $this->hasMany(PoLansirMobil::class, 'lansir_id');
    }

    public function tims()
    {
        return $this->hasMany(PoLansirTim::class, 'lansir_id');
    }

    // Total berat seluruh mobil dalam event ini
    public function getTotalBeratAttribute(): float
    {
        return $this->mobils->sum('berat') ?? 0;
    }

    // Total ongkos lansir (berat × ongkos per mobil)
    public function getTotalOngkosAttribute(): float
    {
        return $this->mobils->sum(fn($m) => ($m->berat ?? 0) * ($m->ongkos ?? 0));
    }

    // Total upah bongkar — pakai berat masing-masing tim.
    public function getTotalUpahAttribute(): float
    {
        return $this->tims->sum(fn($t) => ($t->berat ?? 0) * ($t->upah ?? 0));
    }
}
