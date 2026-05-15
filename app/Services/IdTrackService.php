<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class IdtrackService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    public function __construct()
    {
        $this->baseUrl = config('Idtrack.idtrack.base_url');
        $this->username = config('Idtrack.idtrack.username');
        $this->password = config('Idtrack.idtrack.password');
    }

    // Ambil token, cache 23 jam
    public function getToken(): string
    {
        return Cache::remember('idtrack_token', now()->addHours(23), function () {
            $response = Http::post("{$this->baseUrl}/login/create_token", [
                'Username' => $this->username,
                'Password' => $this->password,
            ]);

            return $response->json('access_token');
        });
    }

    /**
     * Step 3 — Daftar device (DeviceID per mobil).
     * GET /api/device?apikey=[TOKEN]
     */
    public function getDevices(): array
    {
        $token = $this->getToken();

        $response = Http::get("{$this->baseUrl}/api/device", [
            'apikey' => $token,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Step 6 — List kendaraan + posisi/status (struktur nested per user).
     * GET /api/devicetracking?apikey=[TOKEN]
     */
    public function getDeviceTracking(): array
    {
        $token = $this->getToken();

        $response = Http::get("{$this->baseUrl}/api/devicetracking", [
            'apikey' => $token,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Step 4 — List POI / marker.
     * GET /api/marker?apikey=[TOKEN]
     */
    public function getMarkers(): array
    {
        $token = $this->getToken();

        $response = Http::get("{$this->baseUrl}/api/marker", [
            'apikey' => $token,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Step 5 — Buat SPJ untuk satu mobil (ulangi per DeviceID berbeda).
     *
     * @return array{successful: bool, status: int, data: array}
     */
    public function setSPJ(
        int $deviceId,
        int $pickupMarkerId,
        int $destinationMarkerId,
        string $pickupDate,
        string $driver,
        string $nomorSurat,
        string $callbackUrl,
    ): array {
        $token = $this->getToken();

        $response = Http::post("{$this->baseUrl}/api/spj?apikey={$token}", [
            'DeviceID' => $deviceId,
            'PickupMarkerID' => $pickupMarkerId,
            'DestinationMarkerID' => $destinationMarkerId,
            'PickupDate' => $pickupDate,
            'Driver' => $driver,
            'NomorSurat' => $nomorSurat,
            'UrlCallback' => $callbackUrl,
        ]);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    // Posisi 1 kendaraan berdasarkan DeviceID
    public function getDevicePosition(int $deviceId): array
    {
        $token = $this->getToken();

        $response = Http::get("{$this->baseUrl}/api/device/{$deviceId}", [
            'apikey' => $token,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Satukan bentuk respons Step 3 dan Step 6 menjadi koleksi baris device
     * (minimal berisi DeviceID + Nopol untuk pencarian & dropdown).
     */
    public function getAllDevicesFlattened(): Collection
    {
        $fromDevice = $this->normalizeDevicesPayload($this->getDevices());
        if ($fromDevice->isNotEmpty()) {
            return $fromDevice;
        }

        return $this->normalizeDevicesPayload($this->getDeviceTracking());
    }

    /**
     * Cari satu device berdasarkan nomor polisi (mencoba Step 3 lalu fallback Step 6).
     *
     * @return array<string, mixed>|null
     */
    public function findDeviceByNopol(string $nopol): ?array
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', $nopol));

        return $this->getAllDevicesFlattened()->first(function ($d) use ($normalized) {
            $deviceNopol = strtoupper(preg_replace('/\s+/', '', (string) ($d['Nopol'] ?? $d['nopol'] ?? '')));

            return $deviceNopol === $normalized;
        });
    }

    /**
     * @param  mixed  $raw  JSON decode dari /api/device atau /api/devicetracking
     */
    private function normalizeDevicesPayload(mixed $raw): Collection
    {
        if (! is_array($raw) || $raw === []) {
            return collect();
        }

        // Array numerik baris device: [ { "DeviceID": …, "Nopol": … }, … ]
        if (array_is_list($raw) && isset($raw[0]) && is_array($raw[0]) && $this->rowHasDeviceId($raw[0])) {
            return collect($raw);
        }

        // /api/devicetracking: [ { "Devices": [ … ] }, … ]
        if (array_is_list($raw) && isset($raw[0]['Devices'])) {
            return collect($raw)->flatMap(fn ($group) => $group['Devices'] ?? []);
        }

        if (isset($raw['Devices']) && is_array($raw['Devices'])) {
            return collect($raw['Devices']);
        }

        if (isset($raw['Data']) && is_array($raw['Data'])) {
            return $this->normalizeDevicesPayload($raw['Data']);
        }

        return collect();
    }

    private function rowHasDeviceId(array $row): bool
    {
        return isset($row['DeviceID']) || isset($row['device_id']);
    }
}
