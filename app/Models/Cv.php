<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Cv extends Model
{
    protected $table = 'cv';

    protected $fillable = ['nama_cv', 'code', 'is_aktif', 'alamat', 'nama_bank', 'no_rekening', 'atas_nama_rekening', 'nama_pimpinan', 'no_dokumen_prefix', 'logo'];

    const BATAS_OMZET = 4_800_000_000; // Rp 48 juta per tahun

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'cv_id');
    }

    /**
     * Hitung omzet CV dalam satu tahun (default: tahun berjalan).
     * Omzet = SUM(jumlah_kg × harga_pt_sum) dari po_penerima_pakan + gudang_lansir_pakan
     */
    public function omzetTahun(?int $year = null): float
    {
        $year = $year ?? now()->year;

        $omzetPo = (float) DB::table('po_penerima_pakan')
            ->join('po_penerima',   'po_penerima.id',   '=', 'po_penerima_pakan.po_penerima_id')
            ->join('po_kendaraan',  'po_kendaraan.id',  '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->where('purchase_orders.cv_id', $this->id)
            ->whereYear('purchase_orders.tanggal_po', $year)
            ->selectRaw('SUM(po_penerima_pakan.jumlah_kg * COALESCE(po_penerima_pakan.harga_pt_sum, 0)) as total')
            ->value('total') ?? 0;

        $omzetGudang = (float) DB::table('gudang_lansir_pakan')
            ->join('gudang_lansir_penerima', 'gudang_lansir_penerima.id', '=', 'gudang_lansir_pakan.penerima_id')
            ->join('gudang_lansir_kendaraan', 'gudang_lansir_kendaraan.id', '=', 'gudang_lansir_penerima.kendaraan_id')
            ->join('gudang_lansir_header', 'gudang_lansir_header.id', '=', 'gudang_lansir_kendaraan.lansir_header_id')
            ->where('gudang_lansir_header.cv_id', $this->id)
            ->whereYear('gudang_lansir_header.tanggal_lansir', $year)
            ->selectRaw('SUM(gudang_lansir_pakan.jumlah_kg * COALESCE(gudang_lansir_pakan.harga_pt_sum, 0)) as total')
            ->value('total') ?? 0;

        return $omzetPo + $omzetGudang;
    }

    public function isMelebihiBatas(?int $year = null): bool
    {
        return $this->omzetTahun($year) >= self::BATAS_OMZET;
    }

    public static function withOmzet(?int $year = null): Collection
    {
        $year = $year ?? now()->year;

        $omzetsPo = DB::table('po_penerima_pakan')
            ->join('po_penerima',   'po_penerima.id',   '=', 'po_penerima_pakan.po_penerima_id')
            ->join('po_kendaraan',  'po_kendaraan.id',  '=', 'po_penerima.po_kendaraan_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'po_kendaraan.po_id')
            ->whereYear('purchase_orders.tanggal_po', $year)
            ->selectRaw('purchase_orders.cv_id, SUM(po_penerima_pakan.jumlah_kg * COALESCE(po_penerima_pakan.harga_pt_sum, 0)) as omzet')
            ->groupBy('purchase_orders.cv_id')
            ->pluck('omzet', 'cv_id');

        $omzetsGudang = DB::table('gudang_lansir_pakan')
            ->join('gudang_lansir_penerima', 'gudang_lansir_penerima.id', '=', 'gudang_lansir_pakan.penerima_id')
            ->join('gudang_lansir_kendaraan', 'gudang_lansir_kendaraan.id', '=', 'gudang_lansir_penerima.kendaraan_id')
            ->join('gudang_lansir_header', 'gudang_lansir_header.id', '=', 'gudang_lansir_kendaraan.lansir_header_id')
            ->whereYear('gudang_lansir_header.tanggal_lansir', $year)
            ->selectRaw('gudang_lansir_header.cv_id, SUM(gudang_lansir_pakan.jumlah_kg * COALESCE(gudang_lansir_pakan.harga_pt_sum, 0)) as omzet')
            ->groupBy('gudang_lansir_header.cv_id')
            ->pluck('omzet', 'cv_id');

        return static::where('is_aktif', true)
            ->get()
            ->map(function ($cv) use ($omzetsPo, $omzetsGudang) {
                $omzetPo = (float) ($omzetsPo[$cv->id] ?? 0);
                $omzetGudang = (float) ($omzetsGudang[$cv->id] ?? 0);
                $cv->omzet_tahun    = $omzetPo + $omzetGudang;
                $cv->melebihi_batas = $cv->omzet_tahun >= self::BATAS_OMZET;
                $cv->persen_omzet   = min(100, round($cv->omzet_tahun / self::BATAS_OMZET * 100, 1));

                return $cv;
            });
    }
}
