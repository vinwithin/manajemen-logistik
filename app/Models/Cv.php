<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Cv extends Model
{
    protected $table = 'cv';

    protected $fillable = ['nama_cv', 'code', 'is_aktif'];

    const BATAS_OMZET = 48_000_000; // Rp 48 juta per tahun

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'cv_id');
    }

    /**
     * Hitung omzet CV dalam satu tahun (default: tahun berjalan)
     * Omzet = SUM(berat × ongkos) dari item selesai/lansir
     */
    public function omzetTahun(?int $year = null): float
    {
        $year = $year ?? now()->year;

        return (float) DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->where('purchase_orders.cv_id', $this->id)
            ->whereIn('purchase_order_items.status', ['selesai', 'lansir'])
            ->whereYear('purchase_orders.tanggal_po', $year)
            ->selectRaw('SUM(purchase_order_items.berat * purchase_order_items.ongkos) as total')
            ->value('total') ?? 0;
    }

    public function isMelebihiBatas(?int $year = null): bool
    {
        return $this->omzetTahun($year) >= self::BATAS_OMZET;
    }

    
    public static function withOmzet(?int $year = null): Collection
    {
        $year = $year ?? now()->year;

        $omzets = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.po_id')
            ->whereIn('purchase_order_items.status', ['selesai', 'lansir'])
            ->whereYear('purchase_orders.tanggal_po', $year)
            ->selectRaw('purchase_orders.cv_id, SUM(purchase_order_items.berat * purchase_order_items.ongkos) as omzet')
            ->groupBy('purchase_orders.cv_id')
            ->pluck('omzet', 'cv_id');

        return static::where('is_aktif', true)
            ->get()
            ->map(function ($cv) use ($omzets) {
                $cv->omzet_tahun = (float) ($omzets[$cv->id] ?? 0);
                $cv->melebihi_batas = $cv->omzet_tahun >= self::BATAS_OMZET;
                $cv->persen_omzet = min(100, round($cv->omzet_tahun / self::BATAS_OMZET * 100, 1));

                return $cv;
            });
    }
}
