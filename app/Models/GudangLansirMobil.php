<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangLansirMobil extends Model
{
    protected $table = 'gudang_lansir_mobil';

    protected $fillable = [
        'lansir_id',
        'no_polisi',
        'nama_sopir',
        'berat',
        'jumlah_karung',
        'ongkos',
    ];

    protected $casts = [
        'berat'         => 'decimal:2',
        'jumlah_karung' => 'integer',
        'ongkos'        => 'decimal:2',
    ];
    public function lansir(): BelongsTo
    {
        return $this->belongsTo(GudangLansir::class, 'lansir_id');
    }
}
