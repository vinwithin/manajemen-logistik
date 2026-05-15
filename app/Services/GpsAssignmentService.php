<?php

namespace App\Services;

use App\Models\GpsAssignment;
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
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'assignable_type' => get_class($assignable),
                'assignable_id' => $assignable->getKey(),
                'assigned_at' => now(),
                'unassigned_at' => null,
                'catatan' => $catatan,
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
            Log::warning("GPS position fetch failed for device {$assignment->device_id}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Ambil semua device dari Idtrack (untuk dropdown pilih device).
     */
    public function getAvailableDevices(): array
    {
        try {
            $flat = $this->idtrack->getAllDevicesFlattened();

            $activeDeviceIds = GpsAssignment::active()->pluck('device_id')->all();

            return $flat->map(function (array $d) use ($activeDeviceIds) {
                $id = (int) ($d['DeviceID'] ?? $d['device_id'] ?? 0);

                return array_merge($d, [
                    'DeviceID' => $id ?: null,
                    'is_assigned' => $id > 0 && in_array($id, $activeDeviceIds, true),
                ]);
            })->values()->all();
        } catch (\Exception $e) {
            Log::warning('GPS device list fetch failed: '.$e->getMessage());

            return [];
        }
    }

    private function resolveDeviceName(int $deviceId): ?string
    {
        try {
            $row = $this->idtrack->getAllDevicesFlattened()->first(function ($d) use ($deviceId) {
                $id = (int) ($d['DeviceID'] ?? $d['device_id'] ?? 0);

                return $id === $deviceId;
            });

            if (! $row) {
                return null;
            }

            return $row['DeviceName'] ?? $row['device_name'] ?? $row['Nopol'] ?? $row['nopol'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
