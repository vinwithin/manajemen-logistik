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

  
    public static function generateNoSurat(Cv $cv, string $tipe, string $dari): array
    {
        $tahun = (int) date('Y', strtotime($dari));
        $prefix = $cv->no_dokumen_prefix ?? strtoupper(substr($cv->nama_cv, 0, 6));

        // Ambil urutan terakhir untuk CV ini di tahun yang sama
        $lastUrutan = static::where('cv_id', $cv->id)
            ->where('tipe', $tipe)
            ->whereYear('dari', $tahun)
            ->max('urutan') ?? 0;

        $urutan = $lastUrutan + 1;

        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulan = $bulanRomawi[(int) date('n', strtotime($dari)) - 1];

        $noSurat = "{$urutan}-{$prefix}/{$bulan}/{$tahun}";

        return ['no_surat' => $noSurat, 'urutan' => $urutan];
    }
}
