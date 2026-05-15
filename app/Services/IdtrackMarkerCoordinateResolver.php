<?php

namespace App\Services;

use App\Models\Penerima;
use App\Models\Tujuan;
use Illuminate\Support\Facades\Cache;

/**
 * Menyelesaikan lat/lng/nama untuk IDMarker Idtrack (API marker + fallback master data).
 */
class IdtrackMarkerCoordinateResolver
{
    public function __construct(private IdtrackService $idtrack) {}

    /**
     * @return array{lat: ?float, lng: ?float, name: ?string}|null
     */
    public function resolve(int $markerId): ?array
    {
        if ($markerId < 1) {
            return null;
        }

        $fromApi = $this->fromMarkersApi($markerId);
        if ($fromApi !== null) {
            return $fromApi;
        }

        $p = Penerima::query()->where('idtrack_marker_id', $markerId)->whereNotNull('lat')->whereNotNull('lng')->first();
        if ($p) {
            return [
                'lat' => (float) $p->lat,
                'lng' => (float) $p->lng,
                'name' => $p->nama,
            ];
        }

        $t = Tujuan::query()->where('idtrack_marker_id', $markerId)->whereNotNull('lat')->whereNotNull('lng')->first();
        if ($t) {
            return [
                'lat' => (float) $t->lat,
                'lng' => (float) $t->lng,
                'name' => $t->nama,
            ];
        }

        return null;
    }

    /**
     * @return array{lat: ?float, lng: ?float, name: ?string}|null
     */
    private function fromMarkersApi(int $markerId): ?array
    {
        $markers = Cache::remember('idtrack_markers_list', now()->addHour(), function () {
            return $this->idtrack->getMarkers();
        });

        if (! is_array($markers)) {
            return null;
        }

        foreach ($markers as $m) {
            if (! is_array($m)) {
                continue;
            }
            $id = (int) ($m['IDMarker'] ?? $m['IdMarker'] ?? $m['id_marker'] ?? 0);
            if ($id !== $markerId) {
                continue;
            }
            $lat = $m['Lat'] ?? $m['lat'] ?? null;
            $lng = $m['Lng'] ?? $m['lng'] ?? null;
            $name = $m['Name'] ?? $m['name'] ?? null;

            return [
                'lat' => $lat !== null && $lat !== '' ? (float) $lat : null,
                'lng' => $lng !== null && $lng !== '' ? (float) $lng : null,
                'name' => $name !== null && $name !== '' ? (string) $name : null,
            ];
        }

        return null;
    }
}
