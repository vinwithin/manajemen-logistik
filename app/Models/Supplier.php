<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = ['nama', 'initial'];

    /**
     * Relasi many-to-many dengan Tujuan
     * Supplier bisa punya banyak tujuan dengan ongkos_angkut masing-masing
     */
    public function tujuans(): BelongsToMany
    {
        return $this->belongsToMany(Tujuan::class, 'supplier_tujuan')
            ->withPivot('ongkos_angkut', 'harga_pt_sum', 'jenis_kendaraan')
            ->withTimestamps();
    }

    /**
     * Get ongkos angkut untuk tujuan dan jenis kendaraan tertentu
     */
    public function getOngkosAngkut($tujuanId, $jenisKendaraan = null): float
    {
        $query = $this->tujuans()->where('tujuan_id', $tujuanId);
        
        if ($jenisKendaraan) {
            $query->where('jenis_kendaraan', $jenisKendaraan);
        }
        
        $tujuan = $query->first();
        return $tujuan ? (float) $tujuan->pivot->ongkos_angkut : 0;
    }

    public function getHargaPtSum($tujuanId, $jenisKendaraan = null): float
    {
        $query = $this->tujuans()->where('tujuan_id', $tujuanId);

        if ($jenisKendaraan) {
            $query->where('jenis_kendaraan', $jenisKendaraan);
        }

        $tujuan = $query->first();
        return $tujuan ? (float) ($tujuan->pivot->harga_pt_sum ?? 0) : 0;
    }
}
