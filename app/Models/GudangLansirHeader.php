<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangLansirHeader extends Model
{
    protected $table = 'gudang_lansir_header';

    protected $fillable = [
        'no_lansir',
        'gudang_id',
        'cv_id',
        'tanggal_lansir',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_lansir' => 'date',
    ];

    protected $appends = [
        'total_kg',
        'total_karung',
        'jumlah_kendaraan',
        'jumlah_penerima',
    ];
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'gudang_id');
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function kendaraans(): HasMany
    {
        return $this->hasMany(GudangLansirKendaraan::class, 'lansir_header_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Total KG dari semua kendaraan
    public function getTotalKgAttribute(): float
    {
        return (float) $this->kendaraans->sum('total_kg');
    }

    // Total karung dari semua kendaraan
    public function getTotalKarungAttribute(): int
    {
        return (int) $this->kendaraans->sum('total_karung');
    }

    // Total kendaraan
    public function getJumlahKendaraanAttribute(): int
    {
        return $this->kendaraans->count();
    }

    // Total penerima dari semua kendaraan
    public function getJumlahPenerimaAttribute(): int
    {
        return $this->kendaraans->flatMap->penerimas->count();
    }

    // Generate nomor lansir otomatis
    public static function generateNoLansir(): string
    {
        $prefix = 'GL';
        $date = date('Ymd');
        $lastLansir = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLansir) {
            $lastNumber = (int) substr($lastLansir->no_lansir, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
