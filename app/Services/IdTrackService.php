<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as FacadesLog;

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

    // List semua kendaraan beserta posisi & status
    public function getDeviceTracking(): array
    {
        $token = $this->getToken();

        $response = Http::get("{$this->baseUrl}/api/devicetracking", [
            'apikey' => $token,
        ]);

        return $response->json() ?? [];
    }

    // List semua POI/Marker
    public function getMarkers(): array
    {
        $token = $this->getToken();

        $response = Http::get("{$this->baseUrl}/api/marker", [
            'apikey' => $token,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Set SPJ ke device — Idtrack akan callback saat kendaraan tiba di destination marker.
     */
    public function setSPJ(
        int    $deviceId,
        int    $pickupMarkerId,
        int    $destinationMarkerId,
        string $pickupDate,
        string $driver,
        string $nomorSurat,
        string $callbackUrl,
    ): array {
        $token = $this->getToken();

        $response = Http::post("{$this->baseUrl}/api/spj?apikey={$token}", [
            'DeviceID'             => $deviceId,
            'PickupMarkerID'       => $pickupMarkerId,
            'DestinationMarkerID'  => $destinationMarkerId,
            'PickupDate'           => $pickupDate,
            'Driver'               => $driver,
            'NomorSurat'           => $nomorSurat,
            'UrlCallback'          => $callbackUrl,
        ]);

        return $response->json() ?? [];
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

    public function findDeviceByNopol(string $nopol)
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', $nopol));
        $devices = $this->getDeviceTracking();

        // Flatten dulu semua devices dari semua user
        $allDevices = collect($devices)->flatMap(fn($user) => $user['Devices'] ?? []);

        return $allDevices->first(function ($d) use ($normalized) {
            $deviceNopol = strtoupper(preg_replace('/\s+/', '', $d['Nopol'] ?? ''));
            return $deviceNopol === $normalized;
        });
    }
}
