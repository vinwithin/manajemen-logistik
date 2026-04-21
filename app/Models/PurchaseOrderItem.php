<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'po_id', 'no_polisi', 'no_surat_jalan', 'tujuan_id', 'supplier_id', 'kode_pakan_id',
        'nama_penerima', 'nama_supir', 'hp_supir',
        'berat', 'jumlah_karung', 'ongkos', 'status', 'tiba_at', 'validasi_oleh',
        'bukti_tiba', 'selesai_lansir_at',
    ];

    protected $casts = [
        'tiba_at'           => 'datetime',
        'selesai_lansir_at' => 'datetime',
    ];

    const STATUSES = [
        'pending' => 'Pending',
        'berangkat' => 'Berangkat',
        'selesai' => 'Selesai',
        'lansir' => 'Lansir',
        'batal' => 'Batal',
    ];
    const STATUSEDIT = [
        'pending' => 'Pending',
        'berangkat' => 'Berangkat',
        'batal' => 'Batal',
    ];

    // Status yang dianggap "sudah ditangani" untuk keperluan lock PO
    const DONE_STATUSES = ['selesai', 'lansir', 'batal', 'berangkat'];

    public function po()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function tujuan()
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function kodePakan()
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }

    public function lansirRecords()
    {
        return $this->hasMany(PoItemLansir::class, 'po_item_id');
    }

    public function penerimaList()
    {
        return $this->hasMany(PoItemPenerima::class, 'po_item_id');
    }

    public function oaPayment()
    {
        return $this->hasOne(OaPayment::class, 'po_item_id');
    }

    // Total OA item ini (berat × ongkos)
    public function getTotalOaAttribute(): float
    {
        return ($this->berat ?? 0) * ($this->ongkos ?? 0);
    }
}
