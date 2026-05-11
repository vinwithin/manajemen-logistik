<?php

namespace App\Services;

use App\Models\GpsAssignment;
use App\Models\PoKendaraan;
use App\Models\PoLansirMobil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GpsAssignmentService
{
    public function __construct(private IdtrackService $idtrack) {}

    
    public function assign(Model $assignable, int $deviceId, ?string $catatan = null): GpsAssignment
    {
        return DB::transaction(function () use ($assignable, $deviceId, $catatan) {
            // Unassign device dari entitas lain yang masih aktif
            GpsAssignment::active()
                ->where('device_id', $deviceId)
                ->update(['unassigned_at' => now()]);

            // Unassign device lain yang mungkin masih aktif di entitas ini
            GpsAssignment::active()
                ->where('assignable_type', get_class($assignable))
                ->where('assignable_id', $assignable->getKey())
                ->update(['unassigned_at' => now()]);

            // Ambil nama device dari Idtrack
            $deviceName = $this->resolveDeviceName($deviceId);

            return GpsAssignment::create([
                'device_id'        => $deviceId,
                'device_name'      => $deviceName,
                'assignable_type'  => get_class($assignable),
                'assignable_id'    => $assignable->getKey(),
                'assigned_at'      => now(),
                'unassigned_at'    => null,
                'catatan'          => $catatan,
            ]);
        });
    }

    /**
     * Unassign device dari entitas tertentu.
     */
    public function unassign(Model $assignable): void
    {
        GpsAssignment::active()
            ->where('assignable_type', get_class($assignable))
            ->where('assignable_id', $assignable->getKey())
            ->update(['unassigned_at' => now()]);
    }

    /**
     * Ambil posisi live dari device yang sedang di-assign ke entitas.
     * Return null jika tidak ada device aktif.
     */
    public function getLivePosition(Model $assignable): ?array
    {
        $assignment = GpsAssignment::active()
            ->where('assignable_type', get_class($assignable))
            ->where('assignable_id', $assignable->getKey())
            ->first();

        if (! $assignment) {
            return null;
        }

        try {
            return $this->idtrack->getDevicePosition($assignment->device_id);
        } catch (\Exception $e) {
            Log::warning("GPS position fetch failed for device {$assignment->device_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil semua device dari Idtrack (untuk dropdown pilih device).
     */
    public function getAvailableDevices(): array
    {
        try {
            $devices = $this->idtrack->getDeviceTracking();

            // Tandai device mana yang sedang aktif di-assign
            $activeDeviceIds = GpsAssignment::active()->pluck('device_id')->toArray();

            return collect($devices)->map(function ($device) use ($activeDeviceIds) {
                $device['is_assigned'] = in_array($device['DeviceID'] ?? $device['device_id'] ?? 0, $activeDeviceIds);
                return $device;
            })->all();
        } catch (\Exception $e) {
            Log::warning('GPS device list fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function resolveDeviceName(int $deviceId): ?string
    {
        try {
            $devices = $this->idtrack->getDeviceTracking();
            $device  = collect($devices)->firstWhere('DeviceID', $deviceId)
                     ?? collect($devices)->firstWhere('device_id', $deviceId);
            return $device['DeviceName'] ?? $device['device_name'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
