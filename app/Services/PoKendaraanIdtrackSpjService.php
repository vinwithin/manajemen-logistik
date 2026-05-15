<?php

namespace App\Services;

use App\Models\PoKendaraan;
use Illuminate\Support\Facades\Log;

/**
 * Kirim SPJ Idtrack untuk satu baris po_kendaraan (nomor polisi → device, pickup dari config gudang Padang).
 */
class PoKendaraanIdtrackSpjService
{
    public function __construct(private IdtrackService $idtrack) {}

    /**
     * @return array{success: bool, skipped?: bool, message: string, result?: array}
     */
    public function trySync(PoKendaraan $kendaraan, bool $forceResend = false, ?int $pickupMarkerId = null): array
    {
        $kendaraan->loadMissing(['po', 'penerimas.penerima', 'penerimas.tujuan']);

        $sj = trim((string) ($kendaraan->no_surat_jalan ?? ''));
        if ($sj === '') {
            return [
                'success' => false,
                'skipped' => true,
                'message' => 'Nomor surat jalan belum diisi.',
            ];
        }

        if ($kendaraan->status !== 'berangkat') {
            return [
                'success' => false,
                'skipped' => true,
                'message' => 'SPJ hanya dikirim saat status kendaraan Berangkat.',
            ];
        }

        if ($kendaraan->penerimas->isEmpty()) {
            return [
                'success' => false,
                'skipped' => true,
                'message' => 'Belum ada penerima pada kendaraan ini.',
            ];
        }

        $pickup = (int) ($pickupMarkerId ?? config('Idtrack.idtrack.pickup_marker_id'));
        if ($pickup < 1) {
            return [
                'success' => false,
                'message' => 'Konfigurasi IDTRACK_PICKUP_MARKER_ID (gudang Padang) belum di-set.',
            ];
        }

        if (
            ! $forceResend
            && $kendaraan->idtrack_spj_sent_at
            && $kendaraan->idtrack_spj_nomor_surat === $sj
        ) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'SPJ untuk nomor SJ ini sudah pernah dikirim ke Idtrack.',
            ];
        }

        $destinationMarkerId = $kendaraan->penerimas
            ->sortBy('id')
            ->map(fn ($p) => $p->penerima?->idtrack_marker_id ?? $p->tujuan?->idtrack_marker_id)
            ->filter()
            ->first();

        if (! $destinationMarkerId) {
            return [
                'success' => false,
                'message' => 'Belum ada marker tujuan: isi Marker Idtrack di Master Penerima atau Master Tujuan (fallback).',
            ];
        }

        $device = $this->idtrack->findDeviceByNopol($kendaraan->no_polisi);
        if (! $device) {
            return [
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan di GPS Idtrack (nomor polisi).',
            ];
        }

        $deviceId = (int) ($device['DeviceID'] ?? $device['device_id'] ?? 0);
        if (! $deviceId) {
            return [
                'success' => false,
                'message' => 'Data device dari Idtrack tidak valid (DeviceID).',
            ];
        }

        $callbackUrl = route('idtrack.spj-callback');

        try {
            $apiResult = $this->idtrack->setSPJ(
                deviceId: $deviceId,
                pickupMarkerId: $pickup,
                destinationMarkerId: (int) $destinationMarkerId,
                pickupDate: now()->toIso8601String(),
                driver: $kendaraan->nama_sopir ?? '-',
                nomorSurat: $sj,
                callbackUrl: $callbackUrl,
            );
        } catch (\Throwable $e) {
            Log::warning('Idtrack SPJ exception', [
                'po_kendaraan_id' => $kendaraan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal menghubungi Idtrack: '.$e->getMessage(),
            ];
        }

        if (! ($apiResult['successful'] ?? false)) {
            Log::warning('Idtrack SPJ ditolak', [
                'po_kendaraan_id' => $kendaraan->id,
                'http_status' => $apiResult['status'] ?? null,
                'body' => $apiResult['data'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Idtrack menolak permintaan SPJ (HTTP '.($apiResult['status'] ?? '?').').',
                'result' => $apiResult['data'] ?? [],
            ];
        }

        $kendaraan->forceFill([
            'idtrack_spj_sent_at' => now(),
            'idtrack_spj_nomor_surat' => $sj,
        ])->save();

        return [
            'success' => true,
            'message' => 'SPJ berhasil dikirim ke Idtrack.',
            'result' => $apiResult['data'] ?? [],
        ];
    }
}
