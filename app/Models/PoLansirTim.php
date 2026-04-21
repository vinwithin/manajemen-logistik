<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoLansirTim extends Model
{
    protected $table = 'po_lansir_tim';

    protected $fillable = ['lansir_id', 'nama_tim', 'berat', 'jumlah_karung', 'upah', 'keterangan'];

    public function lansir()
    {
        return $this->belongsTo(PoItemLansir::class, 'lansir_id');
    }
}
