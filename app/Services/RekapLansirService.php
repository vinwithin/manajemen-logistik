<?php

namespace App\Services;

use App\Models\PoPenerimaLansir;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class RekapLansirService
{
    /**
     * Ambil semua lansir (PoPenerimaLansir) beserta mobil-nya untuk PO ini.
     */
    public function getRekapMobil(PurchaseOrder $po): Collection
    {
        return PoPenerimaLansir::with(['mobils', 'penerima.kendaraan'])
            ->whereHas('penerima.kendaraan', fn($q) => $q->where('po_id', $po->id))
            ->get();
    }

    /**
     * Ambil semua lansir beserta tim bongkar-nya untuk PO ini.
     */
    public function getRekapTim(PurchaseOrder $po): Collection
    {
        return PoPenerimaLansir::with(['tims', 'mobils', 'penerima.kendaraan'])
            ->whereHas('penerima.kendaraan', fn($q) => $q->where('po_id', $po->id))
            ->get();
    }

    public function getGrandTotalMobil(PurchaseOrder $po): float
    {
        return (float) $this->getRekapMobil($po)
            ->sum(fn($lansir) => $lansir->total_ongkos);
    }

    public function getGrandTotalTim(PurchaseOrder $po): float
    {
        return (float) $this->getRekapTim($po)
            ->sum(fn($lansir) => $lansir->total_upah);
    }
}
