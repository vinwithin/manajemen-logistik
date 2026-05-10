<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RugiLaba extends Model
{
    protected $table = 'rugi_laba';

    protected $fillable = [
        'cv_id', 'bulan', 'tahun', 'periode_label',
        'gaji', 'atk', 'pembayaran_supplier_lintas', 'pembayaran_mobil_lokal',
        'sharing_fee', 'sharing_profit', 'perjalanan_dinas', 'entertain',
        'adm_bank', 'upah_bongkar', 'upah_muat', 'upah_bongkar_muat',
        'biaya_lain_lain', 'bbm', 'listrik', 'pdam', 'potongan_voucher',
        'catatan', 'created_by',
    ];

    protected $casts = [
        'gaji'                       => 'decimal:2',
        'atk'                        => 'decimal:2',
        'pembayaran_supplier_lintas'  => 'decimal:2',
        'pembayaran_mobil_lokal'     => 'decimal:2',
        'sharing_fee'                => 'decimal:2',
        'sharing_profit'             => 'decimal:2',
        'perjalanan_dinas'           => 'decimal:2',
        'entertain'                  => 'decimal:2',
        'adm_bank'                   => 'decimal:2',
        'upah_bongkar'               => 'decimal:2',
        'upah_muat'                  => 'decimal:2',
        'upah_bongkar_muat'          => 'decimal:2',
        'biaya_lain_lain'            => 'decimal:2',
        'bbm'                        => 'decimal:2',
        'listrik'                    => 'decimal:2',
        'pdam'                       => 'decimal:2',
        'potongan_voucher'           => 'decimal:2',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function harianEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RugiLabaHarian::class, 'rugi_laba_id');
    }

    /**
     * Hitung total per kode_biaya dari entri harian.
     * Return array ['gaji' => 500000, 'bbm' => 200000, ...]
     */
    public function totalHarian(): array
    {
        return $this->harianEntries()
            ->selectRaw('kode_biaya, SUM(nominal) as total')
            ->groupBy('kode_biaya')
            ->pluck('total', 'kode_biaya')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    public function getTotalBiayaOperasionalAttribute(): float
    {
        return (float) (
            $this->gaji + $this->atk + $this->pembayaran_supplier_lintas +
            $this->pembayaran_mobil_lokal + $this->sharing_fee + $this->sharing_profit +
            $this->perjalanan_dinas + $this->entertain + $this->adm_bank +
            $this->upah_bongkar + $this->upah_muat + $this->upah_bongkar_muat +
            $this->biaya_lain_lain + $this->bbm + $this->listrik + $this->pdam
        );
    }

    public static function namaBulan(int $bulan): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$bulan] ?? '-';
    }
}
