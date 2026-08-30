<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RugiLabaHarian extends Model
{
    protected $table = 'rugi_laba_harian';

    protected $fillable = [
        'rugi_laba_id', 'tanggal', 'kode_biaya', 'keterangan', 'nominal', 'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    public function rugLaba(): BelongsTo
    {
        return $this->belongsTo(RugiLaba::class, 'rugi_laba_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Label tampilan untuk kode biaya */
    public static function labelBiaya(): array
    {
        return [
            'gaji' => 'Gaji',
            'biaya_sewa' => 'Biaya Sewa',
            'atk' => 'ATK',
            // 'pembayaran_supplier_lintas' => 'Pembayaran Supplier Lintas',
            'pembayaran_mobil_lokal' => 'Pembayaran Mobil Lokal',
            'sharing_fee' => 'Sharing Fee',
            'sharing_profit' => 'Sharing Profit',
            'perjalanan_dinas' => 'Perjalanan Dinas',
            'entertain' => 'Entertain',
            'adm_bank' => 'Adm Bank',
            'upah_bongkar' => 'Upah Bongkar',
            'upah_muat' => 'Upah Muat',
            'biaya_lain_lain' => 'Biaya Lain-lain',
            'bbm' => 'BBM',
            'listrik' => 'Listrik',
            'pdam' => 'PDAM',
            'lingkungan' => 'Lingkungan',
        ];
    }
}
