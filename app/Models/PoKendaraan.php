<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoKendaraan extends Model
{
    protected $table = 'po_kendaraan';

    protected $fillable = [
        'po_id', 'no_polisi', 'nama_sopir', 'no_surat_jalan', 'supplier_id', 'jumlah_kg', 'jumlah_karung', 'status',
    ];

    const STATUSES = ['pending', 'berangkat', 'selesai', 'batal'];

    const VALID_TRANSITIONS = [
        'pending'   => ['berangkat', 'batal'],
        'berangkat' => ['selesai', 'batal'],
        'selesai'   => [],
        'batal'     => [],
    ];

    public function po(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function penerimas(): HasMany
    {
        return $this->hasMany(PoPenerima::class, 'po_kendaraan_id');
    }

    // Total KG seluruh penerima di kendaraan ini
    public function getTotalKgAttribute(): float
    {
        return (float) $this->penerimas->sum('total_kg');
    }

    // Total OA seluruh penerima di kendaraan ini
    public function getTotalOaAttribute(): float
    {
        return (float) $this->penerimas->sum('total_oa');
    }

    // Total PT SUM seluruh penerima di kendaraan ini
    public function getTotalPtSumAttribute(): float
    {
        return (float) $this->penerimas->sum('total_pt_sum');
    }
}
