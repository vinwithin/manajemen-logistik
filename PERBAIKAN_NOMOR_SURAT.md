# Perbaikan Error Duplikasi Nomor Surat PT Sum

## Masalah yang Ditemukan

1. **Tipe Enum Tidak Konsisten**
   - Migrasi awal: `enum('ptsum', 'supplier')`
   - Controller Gudang Lansir menggunakan: `'gudang_ptsum'`
   - Menyebabkan error truncated/tidak valid

2. **Race Condition**
   - Jika user mengklik export berkali-kali dengan cepat
   - Bisa terjadi duplikasi data karena tidak ada locking
   - Multiple request bisa membaca urutan yang sama sebelum insert

3. **Unique Constraint**
   - Constraint: `unique_dokumen_periode` pada `(cv_id, dari, sampai, tipe)`
   - Bisa menyebabkan error duplicate key jika ada race condition

## Solusi yang Diterapkan

### 1. Update Enum Tipe Dokumen

**File**: `database/migrations/2026_05_10_073216_update_po_periode_dokumen_tipe_enum.php`

Menambahkan nilai enum baru:
- `'gudang_ptsum'` - untuk export PDF PT Sum dari Gudang Lansir
- `'gudang_supplier'` - untuk export PDF Supplier dari Gudang Lansir

```php
DB::statement("ALTER TABLE po_periode_dokumen MODIFY COLUMN tipe ENUM('ptsum', 'supplier', 'gudang_ptsum', 'gudang_supplier') DEFAULT 'ptsum'");
```

### 2. Implementasi Database Transaction dengan Locking

**File**: `app/Http/Controllers/GudangLansirController.php`

Menggunakan `DB::transaction()` dan `lockForUpdate()`:

```php
$dokumen = \DB::transaction(function () use ($cvId, $from, $to, $cv, $request) {
    // Cek dengan lock untuk menghindari race condition
    $existing = PoPeriodeDokumen::where('cv_id', $cvId)
        ->where('dari', $from)
        ->where('sampai', $to)
        ->where('tipe', 'gudang_ptsum')
        ->lockForUpdate() // Lock row
        ->first();

    if ($existing) {
        return $existing;
    }

    // Generate dan create baru
    $generated = PoPeriodeDokumen::generateNoSurat($cv, 'gudang_ptsum', $from);
    return PoPeriodeDokumen::create([...]);
});
```

**File**: `app/Http/Controllers/PurchaseOrderController.php`

Implementasi yang sama untuk export PO PT Sum.

### 3. Cara Kerja Locking

1. **lockForUpdate()**: Mengunci row yang dibaca sampai transaction selesai
2. Request lain yang mencoba membaca row yang sama akan menunggu
3. Mencegah multiple request membaca urutan yang sama
4. Memastikan hanya satu dokumen yang dibuat per periode

## Cara Menjalankan Perbaikan

1. **Jalankan Migration**
   ```bash
   php artisan migrate
   ```

2. **Clear Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

3. **Test Export**
   - Buka halaman Gudang Lansir
   - Klik "PDF PT Sum"
   - Pilih periode dan CV
   - Centang "Buat Nomor Surat"
   - Klik Export PDF
   - Coba export ulang dengan periode yang sama
   - Seharusnya menggunakan nomor surat yang sudah ada (tidak duplikasi)

## Verifikasi

Setelah perbaikan, perilaku yang diharapkan:

1. ✅ Export pertama kali: membuat nomor surat baru
2. ✅ Export ulang periode yang sama: menggunakan nomor surat yang sudah ada
3. ✅ Tidak ada error "truncated" atau "duplicate key"
4. ✅ Nomor urut increment dengan benar per CV per tahun
5. ✅ Multiple user export bersamaan: tidak terjadi duplikasi

## Format Nomor Surat

**Purchase Order PT Sum**: `{urutan}-{kodeCV}/{prefix}/{bulan}/{tahun}`
- Contoh: `4-CV1/TR-JBI/GJ/III/2026`

**Gudang Lansir PT Sum**: `{urutan}-{kodeCV}/{prefix}/{bulan}/{tahun}`
- Contoh: `5-CV1/TR-JBI/GJ/III/2026`

Urutan di-reset setiap tahun baru per CV.

## Catatan Penting

- Tipe `'ptsum'` untuk Purchase Order
- Tipe `'gudang_ptsum'` untuk Gudang Lansir
- Keduanya memiliki urutan terpisah per CV per tahun
- Database transaction memastikan atomicity
- Lock mencegah race condition
