# Summary: Perubahan DP & OA Payment per Kendaraan

## Tanggal: 10 Mei 2026

## Problem Statement
User meminta perubahan logika pembayaran OA (Ongkos Angkut) dan DP (Down Payment) Supplier:
- **Sebelumnya**: 1 record OaPayment per penerima (bisa ada banyak record per kendaraan)
- **Sekarang**: 1 record OaPayment per kendaraan (mencakup semua penerima + DP)

## Changes Made

### 1. PurchaseOrderController.php - store() method
**File**: `app/Http/Controllers/PurchaseOrderController.php`

**Perubahan Logika:**
```php
// BEFORE: Create OaPayment per penerima
foreach ($penerima as $p) {
    OaPayment::create([
        'po_penerima_id' => $p->id,
        'jumlah_tagihan' => $p->total_oa,
        // ...
    ]);
}

// AFTER: Create 1 OaPayment per kendaraan
$totalTagihanKendaraan = 0;
foreach ($penerima as $p) {
    // Akumulasi total OA
    $totalTagihanKendaraan += $p->total_oa;
}

OaPayment::create([
    'po_kendaraan_id' => $kendaraan->id,
    'po_penerima_id' => null, // NULL!
    'jumlah_tagihan' => $totalTagihanKendaraan,
    'jumlah_bayar' => $dpNominal,
    'tipe_pembayaran' => 'oa',
    // ...
]);
```

### 2. PoKendaraan Model
**File**: `app/Models/PoKendaraan.php`

**Tambahan Relation:**
```php
// Pembayaran OA untuk kendaraan ini (termasuk DP jika ada)
public function oaPayment()
{
    return $this->hasOne(OaPayment::class, 'po_kendaraan_id')
        ->where('tipe_pembayaran', 'oa');
}
```

### 3. RekapOaController.php - Complete Rewrite
**File**: `app/Http/Controllers/RekapOaController.php`

**Perubahan Query:**
```php
// BEFORE: Query PoPenerima
$query = PoPenerima::with(['kendaraan.po.cv', ...])
    ->whereIn('status', ['selesai', 'batal'])
    ->whereHas('kendaraan.po', ...)

// AFTER: Query PoKendaraan
$query = \App\Models\PoKendaraan::with(['po.cv', 'supplier', 'penerimas', 'oaPayment'])
    ->whereIn('status', ['selesai', 'batal'])
    ->whereHas('po', ...)
```

**Perubahan DataTables Columns:**
- Tambah: `penerima_list` (list semua penerima di kendaraan)
- Tambah: `dp_nominal` (nominal DP)
- Hapus: `tujuan_nama` (karena bisa banyak tujuan per kendaraan)

**Perubahan bayar() & storeBayar():**
```php
// BEFORE: Work with PoPenerima
public function bayar(string $id) {
    $penerima = PoPenerima::findOrFail(decrypt($id));
    // ...
}

// AFTER: Work with PoKendaraan
public function bayar(string $id) {
    $kendaraan = \App\Models\PoKendaraan::findOrFail(decrypt($id));
    // ...
}
```

### 4. View: index.blade.php
**File**: `resources/views/pages/keuangan/oa/index.blade.php`

**Perubahan Kolom Tabel:**
```html
<!-- BEFORE -->
<th>Tujuan</th>
<th>Berat (kg)</th>
<th>Ongkos/kg</th>

<!-- AFTER -->
<th>Penerima</th> <!-- List semua penerima -->
<th>Total KG</th>
<th>Total OA</th>
<th>DP</th> <!-- Kolom baru -->
```

**Perubahan DataTables Columns:**
```javascript
// BEFORE
{ data: 'tujuan_nama', name: 'tujuan_nama' },
{ data: 'berat', name: 'berat' },
{ data: 'ongkos', name: 'ongkos' },

// AFTER
{ data: 'penerima_list', name: 'penerima_list' },
{ data: 'total_kg', name: 'total_kg' },
{ data: 'dp_nominal', name: 'dp_nominal' },
```

### 5. View: bayar.blade.php
**File**: `resources/views/pages/keuangan/oa/bayar.blade.php`

**Perubahan Variable:**
```blade
{{-- BEFORE --}}
$penerima->kendaraan->po->no_po
$penerima->nama_penerima
$penerima->tujuan?->nama

{{-- AFTER --}}
$kendaraan->po->no_po
$kendaraan->no_polisi
$kendaraan->nama_sopir
$kendaraan->dp_nominal (NEW!)

{{-- Loop semua penerima --}}
@foreach ($kendaraan->penerimas as $penerima)
    {{ $penerima->nama_penerima }}
    @foreach ($penerima->pakans as $pk)
        ...
    @endforeach
@endforeach
```

## Key Points

### ✅ Keuntungan
1. **Konsistensi**: 1 kendaraan = 1 record pembayaran
2. **Simplifikasi**: DP dan OA dalam 1 record
3. **User-friendly**: Pembayaran per kendaraan lebih natural
4. **Fleksibilitas**: Mudah tracking dan cicilan

### ⚠️ Breaking Changes
1. Query di `RekapOaController` berubah dari `PoPenerima` ke `PoKendaraan`
2. View `bayar.blade.php` menggunakan variable `$kendaraan` bukan `$penerima`
3. DataTables columns berubah (hapus `tujuan_nama`, tambah `penerima_list` dan `dp_nominal`)

### 📋 Migration Required
```bash
php artisan migrate
```

Migrations:
- `2026_05_10_090000_add_dp_fields_to_po_kendaraan.php`
- `2026_05_10_100000_add_tipe_pembayaran_to_oa_payments.php`

## Testing Steps

1. **Create PO dengan DP**
   - Input DP di form kendaraan
   - Verify: 1 record di `oa_payments` dengan `po_kendaraan_id` dan `po_penerima_id = NULL`
   - Verify: `jumlah_tagihan` = total OA dari semua penerima
   - Verify: `jumlah_bayar` = DP nominal
   - Verify: `status` = 'partial' (jika DP < total) atau 'lunas' (jika DP >= total)

2. **View Rekap OA**
   - Buka `/keuangan/oa`
   - Verify: DataTables menampilkan kendaraan (bukan penerima)
   - Verify: Kolom "Penerima" menampilkan list semua penerima
   - Verify: Kolom "DP" menampilkan nominal DP
   - Verify: Filter (supplier, status, tanggal) berfungsi

3. **Bayar OA**
   - Klik "Bayar" pada kendaraan
   - Verify: Halaman menampilkan info kendaraan
   - Verify: Menampilkan list semua penerima dengan detail pakan
   - Verify: Menampilkan DP yang sudah dibayar
   - Verify: Menampilkan sisa tagihan
   - Input jumlah bayar → Submit
   - Verify: `jumlah_bayar` bertambah
   - Verify: `status` update (partial → lunas jika sudah penuh)

4. **Edge Cases**
   - PO tanpa DP → status 'pending'
   - PO dengan DP = total OA → status 'lunas'
   - Bayar cicilan multiple kali → `jumlah_bayar` akumulatif

## Rollback Plan

Jika ada masalah, rollback dengan:
1. Revert file changes (git)
2. Rollback migration: `php artisan migrate:rollback --step=2`

## Documentation Files

1. `FINAL_DP_OA_KENDARAAN_IMPLEMENTATION.md` - Dokumentasi lengkap
2. `SUMMARY_DP_OA_CHANGES.md` - Summary perubahan (file ini)
3. `ANALISIS_FITUR_DP_SUPPLIER.md` - Analisis awal (archived)
4. `UPDATE_OA_PAYMENT_LOGIC.md` - Update logic (archived)

## Status
✅ **COMPLETED** - Ready for testing

## Next Steps
1. Run migration: `php artisan migrate`
2. Test create PO dengan DP
3. Test rekap OA dan pembayaran
4. Verify data consistency
5. Deploy to production (if tests pass)
