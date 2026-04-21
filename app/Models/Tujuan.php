<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tujuan extends Model
{
    protected $table = 'tujuan';

    protected $fillable = ['type', 'nama', 'is_aktif'];

    const TYPES = [
        'direct'     => 'Direct',
        'gudang'     => 'Gudang',
        'co_farm'    => 'Co Farm',
        'rent_farm'  => 'Rent Farm',
    ];

    public function cv()
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function stoks()
    {
        return $this->hasMany(GudangStok::class, 'tujuan_id');
    }
}
