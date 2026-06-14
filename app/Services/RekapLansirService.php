<?php

namespace App\Services;

use App\Models\PoPenerimaLansir;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class RekapLansirService
{
    /**
     * Ambil seluruh data rekap PO dari sumber yang sama.
     */
    public function getRekapPo(PurchaseOrder $po): Collection
    {
        return PoPenerimaLansir::with([
            'mobils',
            'tims',
            'penerima.kendaraan',
            'penerima.tujuan',
        ])
            ->whereHas('penerima.kendaraan', fn ($q) => $q->where('po_id', $po->id))
            ->orderBy('tanggal_lansir')
            ->orderBy('id')
            ->get();
    }

    public function getRekapMobil(PurchaseOrder $po): Collection
    {
        return $this->getRekapPo($po);
    }

    public function getRekapTim(PurchaseOrder $po): Collection
    {
        return $this->prepareRekapTim($this->getRekapPo($po));
    }

    public function prepareRekapTim(Collection $rekap): Collection
    {
        return $rekap->each(function (PoPenerimaLansir $lansir) {
            $lansir->total_berat_calculated = (float) $lansir->tims->sum('berat');
        });
    }

    public function getGrandTotalMobil(PurchaseOrder|Collection $source): float
    {
        $rekap = $source instanceof PurchaseOrder ? $this->getRekapPo($source) : $source;

        return (float) $rekap
            ->sum(fn ($lansir) => $lansir->total_ongkos);
    }

    public function getGrandTotalTim(PurchaseOrder|Collection $source): float
    {
        $rekap = $source instanceof PurchaseOrder ? $this->getRekapPo($source) : $source;

        return (float) $rekap
            ->sum(fn ($lansir) => $lansir->total_upah);
    }
}
