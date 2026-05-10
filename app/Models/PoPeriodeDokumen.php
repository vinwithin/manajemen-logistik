<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoPeriodeDokumen extends Model
{
    protected $table = 'po_periode_dokumen';

    protected $fillable = ['cv_id', 'dari', 'sampai', 'no_surat', 'urutan', 'tipe', 'catatan', 'created_by'];
    protected $casts = [
        'dari' => 'date',
        'sampai' => 'date',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    /**
     * Generate nomor surat berikutnya untuk CV ini.
     * Format: {urutan}-{kodeCV}/{prefix}/{bulan_romawi}/{tahun}
     * Contoh: 4-CV1/TR-JBI/GJ/III/2026
     *
     * kodeCV  = singkatan CV (diambil dari nama_cv, huruf kapital saja, maks 4 karakter)
     * prefix  = no_dokumen_prefix dari CV
     * Urutan di-reset setiap tahun baru per CV.
     */
    public static function generateNoSurat(Cv $cv, string $tipe, string $dari): array
    {
        $tahun = (int) date('Y', strtotime($dari));
        $prefix = $cv->no_dokumen_prefix ?? strtoupper(substr($cv->nama_cv, 0, 6));

        // Kode CV: ambil huruf kapital dari nama_cv, maks 4 karakter
        // Contoh: "CV Harapan Jaya" → "CVHJ", "PT Sumber Makmur" → "PTSM"
        preg_match_all('/[A-Z0-9]/', strtoupper($cv->nama_cv), $matches);
        $kodeCV = implode('', array_slice($matches[0], 0, 4));
        if (empty($kodeCV)) {
            $kodeCV = strtoupper(substr(preg_replace('/\s+/', '', $cv->nama_cv), 0, 4));
        }

        // Ambil urutan terakhir untuk CV ini di tahun yang sama
        $lastUrutan = static::where('cv_id', $cv->id)
            ->where('tipe', $tipe)
            ->whereYear('dari', $tahun)
            ->max('urutan') ?? 0;

        $urutan = $lastUrutan + 1;

        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulan = $bulanRomawi[(int) date('n', strtotime($dari)) - 1];

        $noSurat = "{$urutan}-{$kodeCV}/{$prefix}/{$bulan}/{$tahun}";

        return ['no_surat' => $noSurat, 'urutan' => $urutan];
    }
}
