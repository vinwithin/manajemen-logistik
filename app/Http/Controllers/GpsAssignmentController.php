<?php

namespace App\Http\Controllers;

use App\Models\GpsAssignment;
use App\Models\PoKendaraan;
use App\Models\PoLansirMobil;
use App\Services\GpsAssignmentService;
use App\Services\IdtrackMarkerCoordinateResolver;
use App\Services\IdtrackService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GpsAssignmentController extends Controller
{
    public function __construct(
        private GpsAssignmentService $service,
        private IdtrackService $idtrack,
        private IdtrackMarkerCoordinateResolver $markerResolver,
    ) {}

    public function trackingMap(): View
    {
        $assignments = GpsAssignment::active()
            ->with('assignable')
            ->get();

        return view('pages.gps.tracking', compact('assignments'));
    }

    public function allPositions(): JsonResponse
    {
        $activeAssignments = GpsAssignment::active()
            ->with([
                'assignable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        PoKendaraan::class => ['idtrackMarkerVisits', 'po'],
                    ]);
                },
            ])
            ->get();

        if ($activeAssignments->isEmpty()) {
            return response()->json(['success' => true, 'positions' => []]);
        }

        try {
            $allDevices = $this->idtrack->getAllDevicesFlattened();
            Log::info('GPS Tracker devices (flattened)', ['count' => $allDevices->count()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal terhubung ke GPS tracker.'], 500);
        }

        $deviceMap = $allDevices->keyBy(fn ($d) => (int) ($d['DeviceID'] ?? $d['device_id'] ?? 0));

        $positions = [];

        foreach ($activeAssignments as $assignment) {
            $device = $deviceMap->get($assignment->device_id);

            if (! $device) {
                continue;
            }

            $deviceId = (int) ($device['DeviceID'] ?? $device['device_id'] ?? 0);

            try {
                $pos = $this->idtrack->getDevicePosition($deviceId);
            } catch (\Exception $e) {
                $pos = [];
            }

            $lat = $pos['Latitude'] ?? null;
            $lng = $pos['Longitude'] ?? null;

            if (! $lat || ! $lng) {
                continue;
            }

            $assignable = $assignment->assignable;
            if (! $assignable) {
                continue;
            }

            $label = 'GPS Device';
            $noPo = null;
            $nopol = $device['Nopol'] ?? $assignment->device_name;

            // Determine label based on assignable type
            if ($assignable instanceof PoKendaraan) {
                $label = 'Kendaraan PO: '.$assignable->no_polisi;
                $noPo = $assignable->po?->no_po;
                $nopol = $assignable->no_polisi;
            } elseif ($assignable instanceof PoLansirMobil) {
                $label = 'Mobil Lansir: '.$assignable->no_polisi;
                $nopol = $assignable->no_polisi;
            }

            $visitedMarkers = [];
            $poKendaraanId = null;
            if ($assignable instanceof PoKendaraan) {
                $poKendaraanId = $assignable->id;
                $visitedMarkers = $this->visitedMarkersPayloadForKendaraan($assignable);
            }

            $positions[] = [
                'device_id' => $deviceId,
                'device_name' => $nopol,
                'label' => $label,
                'no_po' => $noPo,
                'type' => $assignable instanceof PoKendaraan ? 'kendaraan' : 'lansir',
                'assignable_id' => $assignable->id,
                'po_kendaraan_id' => $poKendaraanId,
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $pos['Speed'] ?? null,
                'address' => $pos['Address'] ?? null,
                'last_update' => $pos['GPSTime'] ?? null,
                'gps_time' => $pos['GPSTime'] ?? null,
                'DriverName' => $pos['DriverName'] ?? null,
                'statusEng' => $pos['StatusEng'] ?? null,
                'phone' => $pos['Phone'] ?? null,
                'imei' => $pos['Imei'] ?? null,
                'visited_markers' => $visitedMarkers,
            ];
        }

        return response()->json(['success' => true, 'positions' => $positions]);
    }

    public function positionByNopol(Request $request): JsonResponse
    {
        $request->validate([
            'nopol' => 'required|string',
            'po_kendaraan_id' => 'nullable|integer|exists:po_kendaraan,id',
        ]);

        try {
            $device = $this->idtrack->findDeviceByNopol($request->nopol);

            if (! $device) {
                return response()->json([
                    'success' => false,
                    'message' => "Kendaraan {$request->nopol} tidak ditemukan di GPS tracker.",
                ], 404);
            }

            $deviceId = (int) ($device['DeviceID'] ?? $device['device_id'] ?? 0);
            $position = $this->idtrack->getDevicePosition($deviceId);

            $visitedMarkers = [];
            if ($request->filled('po_kendaraan_id')) {
                $kendaraan = PoKendaraan::with('idtrackMarkerVisits')->find($request->integer('po_kendaraan_id'));
                if ($kendaraan) {
                    $normalizedK = strtoupper(preg_replace('/\s+/', '', (string) $kendaraan->no_polisi));
                    $normalizedReq = strtoupper(preg_replace('/\s+/', '', (string) $request->nopol));
                    if ($normalizedK === $normalizedReq) {
                        $visitedMarkers = $this->visitedMarkersPayloadForKendaraan($kendaraan);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'device_id' => $deviceId,
                'nopol' => $device['Nopol'],
                'position' => $position,
                'DriverName' => $position['DriverName'] ?? null,
                'statusEng' => $position['StatusEng'] ?? null,
                'phone' => $position['Phone'] ?? null,
                'imei' => $position['Imei'] ?? null,
                'lat' => $position['Latitude'] ?? null,
                'lng' => $position['Longitude'] ?? null,
                'speed' => $position['Speed'] ?? null,
                'address' => $position['Address'] ?? null,
                'gps_time' => $position['GPSTime'] ?? null,
                'visited_markers' => $visitedMarkers,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data GPS.'], 500);
        }
    }

    public function devices(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'devices' => $this->service->getAvailableDevices(),
        ]);
    }

    public function assignKendaraan(Request $request, int $kendaraanId): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|integer',
            'catatan' => 'nullable|string|max:255',
        ]);

        $kendaraan = PoKendaraan::findOrFail($kendaraanId);
        $assignment = $this->service->assign($kendaraan, $request->device_id, $request->catatan);

        return response()->json([
            'success' => true,
            'message' => 'GPS berhasil di-assign ke kendaraan.',
            'assignment' => $assignment,
        ]);
    }

    public function assignLansirMobil(Request $request, int $mobilId): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|integer',
            'catatan' => 'nullable|string|max:255',
        ]);

        $mobil = PoLansirMobil::findOrFail($mobilId);
        $assignment = $this->service->assign($mobil, $request->device_id, $request->catatan);

        return response()->json([
            'success' => true,
            'message' => 'GPS berhasil di-assign ke mobil lansir.',
            'assignment' => $assignment,
        ]);
    }

    public function unassignKendaraan(int $kendaraanId): JsonResponse
    {
        $kendaraan = PoKendaraan::findOrFail($kendaraanId);
        $this->service->unassign($kendaraan);

        return response()->json(['success' => true, 'message' => 'GPS berhasil dilepas dari kendaraan.']);
    }

    public function unassignLansirMobil(int $mobilId): JsonResponse
    {
        $mobil = PoLansirMobil::findOrFail($mobilId);
        $this->service->unassign($mobil);

        return response()->json(['success' => true, 'message' => 'GPS berhasil dilepas dari mobil lansir.']);
    }

    public function positionKendaraan(int $kendaraanId): JsonResponse
    {
        $kendaraan = PoKendaraan::findOrFail($kendaraanId);
        $position = $this->service->getLivePosition($kendaraan);

        if (! $position) {
            return response()->json(['success' => false, 'message' => 'Tidak ada GPS aktif pada kendaraan ini.'], 404);
        }

        return response()->json(['success' => true, 'position' => $position]);
    }

    public function positionLansirMobil(int $mobilId): JsonResponse
    {
        $mobil = PoLansirMobil::findOrFail($mobilId);
        $position = $this->service->getLivePosition($mobil);

        if (! $position) {
            return response()->json(['success' => false, 'message' => 'Tidak ada GPS aktif pada mobil lansir ini.'], 404);
        }

        return response()->json(['success' => true, 'position' => $position]);
    }

    public function historyKendaraan(int $kendaraanId): JsonResponse
    {
        $kendaraan = PoKendaraan::findOrFail($kendaraanId);
        $history = $kendaraan->gpsAssignments()->latest('assigned_at')->get();

        return response()->json(['success' => true, 'history' => $history]);
    }

    /**
     * Marker Idtrack yang sudah dikunjungi (untuk peta PO / tracking).
     *
     * @return array<int, array{idtrack_marker_id: int, lat: float, lng: float, arrived_at: ?string, name: ?string}>
     */
    private function visitedMarkersPayloadForKendaraan(PoKendaraan $kendaraan): array
    {
        $visitedMarkers = [];
        foreach ($kendaraan->idtrackMarkerVisits as $v) {
            $lat = $v->lat;
            $lng = $v->lng;
            $name = $v->marker_name;
            if ($lat === null || $lng === null) {
                $c = $this->markerResolver->resolve((int) $v->idtrack_marker_id);
                if ($c !== null) {
                    $lat = $lat ?? $c['lat'];
                    $lng = $lng ?? $c['lng'];
                    $name = $name ?? $c['name'];
                }
            }
            if ($lat !== null && $lng !== null) {
                $visitedMarkers[] = [
                    'idtrack_marker_id' => $v->idtrack_marker_id,
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'arrived_at' => $v->arrived_at?->toIso8601String(),
                    'name' => $name,
                ];
            }
        }

        return $visitedMarkers;
    }
}
