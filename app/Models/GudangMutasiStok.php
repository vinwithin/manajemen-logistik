<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangMutasiStok extends Model
{
    protected $table = 'gudang_mutasi_stok';

    protected $fillable = [
        'tujuan_id',
        'kode_pakan_id',
        'tipe',
        'jumlah_kg',
        'jumlah_karung',
        'referensi_tipe',
        'referensi_id',
        'po_penerima_id',
        'gudang_lansir_pakan_id',
        'saldo_kg_after',
        'saldo_karung_after',
    ];

    protected $casts = [
        'jumlah_kg'           => 'decimal:2',
        'saldo_kg_after'      => 'decimal:2',
        'jumlah_karung'       => 'integer',
        'saldo_karung_after'  => 'integer',
    ];

    // ── Relasi ────────────────────────────────────────────────────────────

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }

    public function kodePakan(): BelongsTo
    {
        return $this->belongsTo(KodePakan::class, 'kode_pakan_id');
    }

    /** Untuk mutasi masuk dari PO penerima */
    public function poPenerima(): BelongsTo
    {
        return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
    }

    /** Untuk mutasi keluar dari lansir gudang */
    public function gudangLansirPakan(): BelongsTo
    {
        return $this->belongsTo(GudangLansirPakan::class, 'gudang_lansir_pakan_id');
    }

    // ── Accessor helpers ──────────────────────────────────────────────────

    /**
     * Ambil kendaraan (no_polisi, no_surat_jalan) dari lansir gudang.
     * Trace: mutasi → gudangLansirPakan → penerima → kendaraan
     */
    public function getKendaraanLansirAttribute(): ?GudangLansirKendaraan
    {
        return $this->gudangLansirPakan?->penerima?->kendaraan;
    }

    /**
     * Ambil header lansir (no_lansir, tanggal) dari lansir gudang.
     * Trace: mutasi → gudangLansirPakan → penerima → kendaraan → lansirHeader
     */
    public function getHeaderLansirAttribute(): ?GudangLansirHeader
    {
        return $this->getKendaraanLansirAttribute()?->lansirHeader;
    }

    /**
     * Ambil nama penerima dari lansir gudang.
     */
    public function getNamaPenerimaLansirAttribute(): ?string
    {
        return $this->gudangLansirPakan?->penerima?->nama_penerima;
    }

    /**
     * No. Surat Jalan dari kendaraan lansir.
     */
    public function getNoSuratJalanAttribute(): ?string
    {
        return $this->getKendaraanLansirAttribute()?->no_surat_jalan;
    }

    /**
     * No. Polisi dari kendaraan lansir.
     */
    public function getNoPlatAttribute(): ?string
    {
        return $this->getKendaraanLansirAttribute()?->no_polisi;
    }
}
