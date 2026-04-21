<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoItemPenerima extends Model
{
    protected $table = 'po_item_penerima';

    protected $fillable = ['po_item_id', 'nama'];

    public function item()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }
}
