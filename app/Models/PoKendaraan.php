<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PoKendaraan extends Model
{
    protected $table = 'po_kendaraan';

    protected $fillable = [
        'po_id', 'no_polisi', 'nama_sopir', 'no_surat_jalan', 'supplier_id', 'jenis_kendaraan', 'jumlah_kg', 'jumlah_karung', 'status',
        'dp_nominal', 'dp_persen', 'dp_tanggal', 'dp_metode', 'dp_keterangan',
    ];

    protected $casts = [
        'dp_tanggal' => 'date',
        'dp_nominal' => 'decimal:2',
        'dp_persen' => 'decimal:2',
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

    // Pembayaran OA untuk kendaraan ini (termasuk DP jika ada)
    public function oaPayment()
    {
        return $this->hasOne(OaPayment::class, 'po_kendaraan_id')
            ->whereIn('tipe_pembayaran', ['oa', 'dp_supplier']);
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

    // Total tagihan supplier (untuk perhitungan DP)
    // Total = SUM(jumlah_kg × ongkos_oa) dari semua pakan
    public function getTotalTagihanSupplierAttribute(): float
    {
        return $this->penerimas->sum(function ($penerima) {
            return $penerima->pakans->sum(function ($pakan) {
                return $pakan->jumlah_kg * ($pakan->ongkos_oa ?? 0);
            });
        });
    }

    // Sisa tagihan setelah DP
    public function getSisaTagihanAttribute(): float
    {
        return max(0, $this->total_tagihan_supplier - $this->dp_nominal);
    }

    // Status pembayaran DP
    public function getStatusPembayaranAttribute(): string
    {
        if ($this->dp_nominal == 0) {
            return 'Belum Bayar DP';
        }
        
        if ($this->dp_nominal >= $this->total_tagihan_supplier) {
            return 'Lunas';
        }
        
        return 'DP ' . number_format($this->dp_persen ?? 0, 0) . '%';
    }

    // Badge class untuk status pembayaran
    public function getStatusPembayaranBadgeAttribute(): string
    {
        if ($this->dp_nominal == 0) {
            return 'bg-secondary';
        }
        
        if ($this->dp_nominal >= $this->total_tagihan_supplier) {
            return 'bg-success';
        }
        
        return 'bg-warning';
    }

    /** Semua riwayat assignment GPS */
    public function gpsAssignments(): MorphMany
    {
        return $this->morphMany(GpsAssignment::class, 'assignable');
    }

    /** Assignment GPS yang sedang aktif */
    public function activeGps(): MorphOne
    {
        return $this->morphOne(GpsAssignment::class, 'assignable')->whereNull('unassigned_at');
    }
}
