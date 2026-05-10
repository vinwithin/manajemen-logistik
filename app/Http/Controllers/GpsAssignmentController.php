<?php

namespace App\Http\Controllers;

use App\Models\GpsAssignment;
use App\Models\GudangLansirMobil;
use App\Models\PoKendaraan;
use App\Models\PoLansirMobil;
use App\Services\GpsAssignmentService;
use App\Services\IdtrackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GpsAssignmentController extends Controller
{
    public function __construct(
        private GpsAssignmentService $service,
        private IdtrackService $idtrack,
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
        // Ambil hanya assignment GPS yang masih aktif (belum di-unassign)
        $activeAssignments = GpsAssignment::active()
            ->with('assignable')
            ->get();

        if ($activeAssignments->isEmpty()) {
            return response()->json(['success' => true, 'positions' => []]);
        }

        try {
            $devices = $this->idtrack->getDeviceTracking();
            Log::info($devices, ['context' => 'GPS Tracker Devices']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal terhubung ke GPS tracker.'], 500);
        }

        $allDevices = collect($devices)->flatMap(fn ($user) => $user['Devices'] ?? []);
        $deviceMap = $allDevices->keyBy('DeviceID');

        $positions = [];

        foreach ($activeAssignments as $assignment) {
            $device = $deviceMap->get($assignment->device_id);

            if (! $device) {
                continue;
            }

            try {
                $pos = $this->idtrack->getDevicePosition($device['DeviceID']);
            } catch (\Exception $e) {
                $pos = [];
            }

            $lat = $pos['Latitude'] ?? null;
            $lng = $pos['Longitude'] ?? null;

            if (! $lat || ! $lng) {
                continue;
            }

            $assignable = $assignment->assignable;
            $label = 'GPS Device';
            $noPo = null;
            $nopol = $device['Nopol'] ?? $assignment->device_name;

            // Determine label based on assignable type
            if ($assignable instanceof PoKendaraan) {
                $label = 'Kendaraan PO: '.$assignable->no_polisi;
                $noPo = $assignable->po?->no_po;
                $nopol = $assignable->no_polisi;
            } elseif ($assignable instanceof GudangLansirMobil) {
                $label = 'Mobil Lansir: '.$assignable->no_polisi;
                $nopol = $assignable->no_polisi;
            }

            $positions[] = [
                'device_id' => $device['DeviceID'],
                'device_name' => $nopol,
                'label' => $label,
                'no_po' => $noPo,
                'type' => $assignable instanceof PoKendaraan ? 'kendaraan' : 'lansir',
                'assignable_id' => $assignable->id,
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $pos['Speed'] ?? null,
                'address' => $pos['Address'] ?? null,
                'last_update' => $pos['GPSTime'] ?? null,
            ];
        }

        return response()->json(['success' => true, 'positions' => $positions]);
    }

    public function positionByNopol(Request $request): JsonResponse
    {
        $request->validate(['nopol' => 'required|string']);

        try {
            $device = $this->idtrack->findDeviceByNopol($request->nopol);
            Log::info("Device found for nopol '{$request->nopol}': ".json_encode($device));

            if (! $device) {
                return response()->json([
                    'success' => false,
                    'message' => "Kendaraan {$request->nopol} tidak ditemukan di GPS tracker.",
                ], 404);
            }

            $position = $this->idtrack->getDevicePosition($device['DeviceID']);

            return response()->json([
                'success' => true,
                'device_id' => $device['DeviceID'],
                'nopol' => $device['Nopol'],
                'position' => $position,
                'lat' => $position['Latitude'] ?? null,
                'lng' => $position['Longitude'] ?? null,
                'speed' => $position['Speed'] ?? null,
                'address' => $position['Address'] ?? null,
                'gps_time' => $position['GPSTime'] ?? null,
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
}
