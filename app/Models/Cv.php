<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Cv extends Model
{
    protected $table = 'cv';

    protected $fillable = ['nama_cv', 'code', 'is_aktif', 'alamat', 'nama_bank', 'no_rekening', 'atas_nama_rekening', 'nama_pimpinan', 'no_dokumen_prefix', 'logo'];

    const BATAS_OMZET = 48_000_000; // Rp 48 juta per tahun

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'cv_id');
    }

    /**
     * Hitung omzet CV dalam satu tahun (default: tahun berjalan).
     * Omzet = SUM(jumlah_kg × harga_pt_sum) dari po_penerima_pakan
     */
    public function omzetTahun(?int $year = null): float
    {
        $year = $year ?? now()->year;

        return (float) DB::table('po_penerima_pakan')
            ->join('po_penerima',   'po_penerima.id',   '=', 'po_penerima_pakan.po_penerima_id')
            ->join('po_kendaraan',  'po_kendaraan.id',  '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->where('purchase_orders.cv_id', $this->id)
            ->whereYear('purchase_orders.tanggal_po', $year)
            ->selectRaw('SUM(po_penerima_pakan.jumlah_kg * COALESCE(po_penerima_pakan.harga_pt_sum, 0)) as total')
            ->value('total') ?? 0;
    }

    public function isMelebihiBatas(?int $year = null): bool
    {
        return $this->omzetTahun($year) >= self::BATAS_OMZET;
    }

    public static function withOmzet(?int $year = null): Collection
    {
        $year = $year ?? now()->year;

        $omzets = DB::table('po_penerima_pakan')
            ->join('po_penerima',   'po_penerima.id',   '=', 'po_penerima_pakan.po_penerima_id')
            ->join('po_kendaraan',  'po_kendaraan.id',  '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->whereYear('purchase_orders.tanggal_po', $year)
            ->selectRaw('purchase_orders.cv_id, SUM(po_penerima_pakan.jumlah_kg * COALESCE(po_penerima_pakan.harga_pt_sum, 0)) as omzet')
            ->groupBy('purchase_orders.cv_id')
            ->pluck('omzet', 'cv_id');

        return static::where('is_aktif', true)
            ->get()
            ->map(function ($cv) use ($omzets) {
                $cv->omzet_tahun    = (float) ($omzets[$cv->id] ?? 0);
                $cv->melebihi_batas = $cv->omzet_tahun >= self::BATAS_OMZET;
                $cv->persen_omzet   = min(100, round($cv->omzet_tahun / self::BATAS_OMZET * 100, 1));

                return $cv;
            });
    }
}
