<?php

namespace App\Http\Controllers;

use App\Models\Penerima;
use App\Models\PoPenerima;
use App\Models\PoKendaraan;
use App\Models\Tujuan;
use App\Services\IdtrackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdtrackController extends Controller
{
    public function __construct(private IdtrackService $idtrack) {}

    /**
     * Ambil semua POI dari Idtrack — untuk dropdown pilih marker di form tujuan/penerima.
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
     * Set SPJ ke device saat kendaraan PO berangkat.
     * Dipanggil otomatis ketika status kendaraan diubah ke 'berangkat'.
     *
     * Pickup  = marker gudang/pabrik (tujuan pertama atau hardcode)
     * Destination = marker tujuan kendaraan (dari penerima pertama)
     */
    public function setSPJForKendaraan(Request $request, int $kendaraanId): JsonResponse
    {
        $request->validate([
            'pickup_marker_id' => 'required|integer',
        ]);

        try {
            $kendaraan = PoKendaraan::with(['po', 'penerimas.tujuan'])->findOrFail($kendaraanId);

            // Ambil device berdasarkan nopol
            $device = $this->idtrack->findDeviceByNopol($kendaraan->no_polisi);
            if (! $device) {
                return response()->json(['success' => false, 'message' => 'Kendaraan tidak ditemukan di GPS.'], 404);
            }

            // Destination = marker tujuan dari penerima pertama yang punya marker
            $destinationMarkerId = $kendaraan->penerimas
                ->map(fn($p) => $p->tujuan?->idtrack_marker_id)
                ->filter()
                ->first();

            if (! $destinationMarkerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tujuan kendaraan belum memiliki Marker ID Idtrack. Set di Master Tujuan.',
                ], 422);
            }

            $callbackUrl = route('idtrack.spj-callback');
            $nomorSurat  = $kendaraan->no_surat_jalan ?? 'PO-' . $kendaraan->po->no_po . '-' . $kendaraan->id;

            $result = $this->idtrack->setSPJ(
                deviceId:            $device['DeviceID'],
                pickupMarkerId:      $request->pickup_marker_id,
                destinationMarkerId: $destinationMarkerId,
                pickupDate:          now()->toIso8601String(),
                driver:              $kendaraan->nama_sopir ?? '-',
                nomorSurat:          $nomorSurat,
                callbackUrl:         $callbackUrl,
            );

            return response()->json([
                'success'      => true,
                'message'      => 'SPJ berhasil di-set ke GPS tracker.',
                'nomor_surat'  => $nomorSurat,
                'result'       => $result,
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

        $deviceId    = $request->input('DeviceID');
        $nomorSurat  = $request->input('NomorSurat');
        $arrivedAt   = $request->input('DestinationMarkerInDate');

        if (! $deviceId || ! $nomorSurat) {
            return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        try {
            // Cari kendaraan berdasarkan no_surat_jalan atau format PO-{no_po}-{id}
            $kendaraan = PoKendaraan::where('no_surat_jalan', $nomorSurat)->first();

            if (! $kendaraan && preg_match('/^PO-.+-(\d+)$/', $nomorSurat, $m)) {
                $kendaraan = PoKendaraan::find($m[1]);
            }

            if (! $kendaraan) {
                Log::warning("Idtrack callback: kendaraan tidak ditemukan untuk NomorSurat={$nomorSurat}");
                return response()->json(['status' => 'ok']); // tetap 200 agar Idtrack tidak retry
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
            Log::error('Idtrack SPJ callback error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
