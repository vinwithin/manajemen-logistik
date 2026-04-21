<?php

namespace App\Services;

use App\Models\PoItemLansir;
use App\Models\PoLansirMobil;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class RekapLansirService
{
    public function getRekapMobil(PurchaseOrder $po): Collection
    {
        return PoItemLansir::with('mobils')
            ->whereHas('item', fn($q) => $q->where('po_id', $po->id))
            ->get();
    }

    public function getRekapTim(PurchaseOrder $po): Collection
    {
        return PoItemLansir::with('tims')
            ->whereHas('item', fn($q) => $q->where('po_id', $po->id))
            ->get();
    }

    public function getGrandTotalMobil(PurchaseOrder $po): float
    {
        return (float) PoLansirMobil::whereHas(
            'lansir.item', fn($q) => $q->where('po_id', $po->id)
        )->get()->sum(fn($m) => ($m->berat ?? 0) * ($m->ongkos ?? 0));
    }

    public function getGrandTotalTim(PurchaseOrder $po): float
    {
        $events = $this->getRekapTim($po)->load('mobils');
        return (float) $events->sum(fn($event) => $event->total_upah);
    }
}
