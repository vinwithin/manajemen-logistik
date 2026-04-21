<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoLansirMobil extends Model
{
    protected $table = 'po_lansir_mobil';

    protected $fillable = ['lansir_id', 'no_polisi', 'nama_sopir', 'berat', 'jumlah_karung', 'ongkos', 'keterangan'];

    public function lansir()
    {
        return $this->belongsTo(PoItemLansir::class, 'lansir_id');
    }

    public function getTotalOngkosAttribute(): float
    {
        return ($this->berat ?? 0) * ($this->ongkos ?? 0);
    }
}
