<?php

namespace App\Http\Controllers;

use App\Models\PoKendaraan;
use App\Models\PoKendaraanIdtrackMarkerVisit;
use App\Services\IdtrackMarkerCoordinateResolver;
use App\Services\IdtrackService;
use App\Services\PoKendaraanIdtrackSpjService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdtrackController extends Controller
{
    public function __construct(
        private IdtrackService $idtrack,
        private PoKendaraanIdtrackSpjService $poKendaraanIdtrackSpj,
        private IdtrackMarkerCoordinateResolver $markerResolver,
    ) {}

    /**
     * Ambil semua POI dari Idtrack — untuk dropdown marker di Master Tujuan & Master Penerima.
     */
    public function markers(): JsonResponse
    {
        try {
            $markers = $this->idtrack->getMarkers();

            return response()->json(['success' => true, 'markers' => $markers]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Set SPJ ke device (Idtrack). Pickup marker default dari config (gudang Padang), bisa di-override.
     * Untuk pemanggilan ulang / debug; dedupe SPJ dinonaktifkan (selalu mencoba kirim ke API).
     */
    public function setSPJForKendaraan(Request $request, int $kendaraanId): JsonResponse
    {
        $request->validate([
            'pickup_marker_id' => 'nullable|integer|min:1',
        ]);

        try {
            $kendaraan = PoKendaraan::with(['po', 'penerimas.tujuan', 'penerimas.penerima'])->findOrFail($kendaraanId);
            $pickupOverride = $request->filled('pickup_marker_id') ? (int) $request->pickup_marker_id : null;

            $out = $this->poKendaraanIdtrackSpj->trySync($kendaraan, true, $pickupOverride);

            if (! $out['success']) {
                $httpStatus = str_contains($out['message'] ?? '', 'tidak ditemukan di GPS') ? 404 : 422;

                return response()->json([
                    'success' => false,
                    'message' => $out['message'],
                    'result' => $out['result'] ?? null,
                ], $httpStatus);
            }

            return response()->json([
                'success' => true,
                'message' => $out['message'],
                'nomor_surat' => trim((string) ($kendaraan->no_hp ?? '')),
                'result' => $out['result'] ?? null,
                'skipped' => $out['skipped'] ?? false,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Callback dari Idtrack saat kendaraan tiba di destination marker.
     * Idtrack POST ke URL ini secara otomatis.
     *
     * Body: { DeviceID, NomorSurat, DestinationMarkerID, DestinationMarkerInDate }
     */
    public function spjCallback(Request $request): JsonResponse
    {
        Log::info('Idtrack SPJ Callback', $request->all());

        $deviceId = $request->input('DeviceID');
        $nomorSurat = $request->input('NomorSurat');
        $arrivedAt = $request->input('DestinationMarkerInDate');
        $destinationMarkerId = (int) $request->input('DestinationMarkerID', 0);

        if (! $deviceId || ! $nomorSurat) {
            return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        try {
            // Cari kendaraan berdasarkan no_hp atau format PO-{no_po}-{id}
            $kendaraan = PoKendaraan::where('no_hp', $nomorSurat)->first();

            if (! $kendaraan && preg_match('/^PO-.+-(\d+)$/', $nomorSurat, $m)) {
                $kendaraan = PoKendaraan::find($m[1]);
            }

            if (! $kendaraan) {
                Log::warning("Idtrack callback: kendaraan tidak ditemukan untuk NomorSurat={$nomorSurat}");

                return response()->json(['status' => 'ok']); // tetap 200 agar Idtrack tidak retry
            }

            if ($destinationMarkerId > 0) {
                $arrivedAtCarbon = $this->parseDestinationArrivedAt($arrivedAt);
                $callbackHash = hash('sha256', implode('|', [
                    (string) $deviceId,
                    (string) $nomorSurat,
                    (string) $destinationMarkerId,
                    (string) ($arrivedAt ?? ''),
                ]));

                if (! PoKendaraanIdtrackMarkerVisit::where('callback_hash', $callbackHash)->exists()) {
                    $coords = $this->markerResolver->resolve($destinationMarkerId) ?? [];
                    $lat = $request->input('DestinationLat', $coords['lat'] ?? null);
                    $lng = $request->input('DestinationLng', $coords['lng'] ?? null);
                    if ($lat !== null && $lat !== '') {
                        $lat = (float) $lat;
                    } else {
                        $lat = isset($coords['lat']) ? (float) $coords['lat'] : null;
                    }
                    if ($lng !== null && $lng !== '') {
                        $lng = (float) $lng;
                    } else {
                        $lng = isset($coords['lng']) ? (float) $coords['lng'] : null;
                    }

                    PoKendaraanIdtrackMarkerVisit::create([
                        'po_kendaraan_id' => $kendaraan->id,
                        'idtrack_marker_id' => $destinationMarkerId,
                        'arrived_at' => $arrivedAtCarbon,
                        'lat' => $lat,
                        'lng' => $lng,
                        'marker_name' => $coords['name'] ?? null,
                        'callback_hash' => $callbackHash,
                    ]);
                }
            }

            // Update status kendaraan ke 'selesai' jika masih berangkat
            if ($kendaraan->status === 'berangkat') {
                $kendaraan->update(['status' => 'selesai']);

                // Update semua penerima yang masih berangkat → tiba
                $kendaraan->penerimas()
                    ->where('status', 'berangkat')
                    ->update(['status' => 'tiba', 'tiba_at' => $arrivedAt ?? now()]);

                Log::info("Idtrack callback: kendaraan {$kendaraan->no_polisi} tiba, status diupdate.");
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Idtrack SPJ callback error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function parseDestinationArrivedAt(mixed $arrivedAt): Carbon
    {
        if ($arrivedAt === null || $arrivedAt === '') {
            return now();
        }
        try {
            return Carbon::parse($arrivedAt);
        } catch (\Exception $e) {
            return now();
        }
    }
}
