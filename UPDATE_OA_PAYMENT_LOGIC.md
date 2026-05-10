# Update: Logika OA Payment untuk Tracking Pembayaran

## Tanggal: 10 Mei 2026

## Perubahan

### Sebelumnya:
Hanya membuat 1 record OaPayment untuk DP Supplier (jika ada DP).

### Sekarang:
Membuat 2 jenis record OaPayment:
1. **DP Supplier** (jika ada DP) - 1 record per kendaraan
2. **Pembayaran OA** - 1 record per penerima

## Alasan Perubahan

Sistem pembayaran supplier memiliki 2 komponen:
1. **Down Payment (DP)**: Dibayar di awal ke supplier (level kendaraan)
2. **Pembayaran OA**: Dibayar setelah barang sampai ke penerima (level penerima)

Untuk tracking yang lengkap, kedua jenis pembayaran ini perlu dicatat di tabel `oa_payments`.

## Implementasi

### 1. Record DP Supplier (Level Kendaraan)

**Kapan dibuat:** Saat create PO, jika ada DP

```php
if (!empty($kendaraanData['dp_nominal']) && $kendaraanData['dp_nominal'] > 0) {
    OaPayment::create([
        'po_kendaraan_id' => $kendaraan->id,
        'po_penerima_id' => null, // DP tidak terkait penerima spesifik
        'supplier_id' => $kendaraan->supplier_id,
        'tipe_pembayaran' => 'dp_supplier',
        'jumlah_tagihan' => 0, // DP tidak punya tagihan, langsung bayar
        'jumlah_bayar' => $kendaraanData['dp_nominal'],
        'tanggal_bayar' => $kendaraanData['dp_tanggal'] ?? now(),
        'metode_bayar' => $kendaraanData['dp_metode'] ?? 'transfer',
        'keterangan' => 'Down Payment Supplier - ' . ($kendaraanData['dp_keterangan'] ?? 'PO: ' . $po->no_po),
        'status' => 'partial', // DP adalah pembayaran sebagian
    ]);
}
```

**Karakteristik:**
- `tipe_pembayaran = 'dp_supplier'`
- `po_kendaraan_id` = ID kendaraan
- `po_penerima_id` = NULL (tidak terkait penerima spesifik)
- `jumlah_tagihan` = 0 (DP langsung bayar, bukan dari tagihan)
- `jumlah_bayar` = Nominal DP
- `status` = 'partial' (DP adalah pembayaran sebagian dari total)

### 2. Record Pembayaran OA (Level Penerima)

**Kapan dibuat:** Saat create PO, untuk setiap penerima yang punya pakan

```php
// Hitung total tagihan OA untuk penerima ini
$totalTagihanOa = 0;
foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
    if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
        continue;
    }

    PoPenerimaPakan::create([...]);

    // Akumulasi total tagihan OA
    $totalTagihanOa += ($pakanData['jumlah_kg'] ?? 0) * ($pakanData['ongkos_oa'] ?? 0);
}

// Buat record OaPayment untuk penerima ini
if ($totalTagihanOa > 0) {
    OaPayment::create([
        'po_penerima_id' => $penerima->id,
        'po_kendaraan_id' => null, // Tidak perlu, sudah ada di penerima
        'supplier_id' => $kendaraan->supplier_id,
        'tipe_pembayaran' => 'oa',
        'jumlah_tagihan' => $totalTagihanOa,
        'jumlah_bayar' => 0, // Belum dibayar
        'tanggal_bayar' => null,
        'metode_bayar' => null,
        'keterangan' => 'Pembayaran OA - ' . $penerima->nama_penerima . ' (PO: ' . $po->no_po . ')',
        'status' => 'pending', // Belum dibayar
    ]);
}
```

**Karakteristik:**
- `tipe_pembayaran = 'oa'`
- `po_penerima_id` = ID penerima
- `po_kendaraan_id` = NULL (tidak perlu, relasi sudah ada via penerima)
- `jumlah_tagihan` = Total OA (Σ jumlah_kg × ongkos_oa)
- `jumlah_bayar` = 0 (belum dibayar)
- `status` = 'pending' (menunggu pembayaran)

## Struktur Data OA Payments

### Contoh Data:

**PO: PO-001**
- **Kendaraan 1** (B 1234 AB, Supplier: PT ABC)
  - DP: Rp 2.000.000
  - **Penerima 1** (Kandang A)
    - Pakan BR1: 1000 kg × Rp 5.000 = Rp 5.000.000
    - Pakan 511: 500 kg × Rp 6.000 = Rp 3.000.000
    - **Total OA Penerima 1**: Rp 8.000.000
  - **Penerima 2** (Kandang B)
    - Pakan BR1: 800 kg × Rp 5.000 = Rp 4.000.000
    - **Total OA Penerima 2**: Rp 4.000.000

**Record di `oa_payments`:**

| id | tipe_pembayaran | po_kendaraan_id | po_penerima_id | supplier_id | jumlah_tagihan | jumlah_bayar | status | keterangan |
|----|-----------------|-----------------|----------------|-------------|----------------|--------------|--------|------------|
| 1  | dp_supplier     | 1               | NULL           | 5           | 0              | 2.000.000    | partial | Down Payment Supplier - PO: PO-001 |
| 2  | oa              | NULL            | 1              | 5           | 8.000.000      | 0            | pending | Pembayaran OA - Kandang A (PO: PO-001) |
| 3  | oa              | NULL            | 2              | 5           | 4.000.000      | 0            | pending | Pembayaran OA - Kandang B (PO: PO-001) |

## Alur Pembayaran

### 1. Saat Create PO:
```
1. User input DP (opsional)
   ↓
2. System create record DP di oa_payments (status: partial)
   ↓
3. User input penerima & pakan
   ↓
4. System hitung total OA per penerima
   ↓
5. System create record OA di oa_payments (status: pending)
```

### 2. Saat Pembayaran OA:
```
1. Barang sampai ke penerima
   ↓
2. Finance bayar OA ke supplier
   ↓
3. Update record OA di oa_payments:
   - jumlah_bayar = nominal bayar
   - tanggal_bayar = tanggal bayar
   - metode_bayar = metode bayar
   - status = 'lunas' atau 'partial'
```

## Keuntungan Pendekatan Ini

### 1. **Tracking Lengkap**
- Semua pembayaran ke supplier tercatat (DP + OA)
- Bisa lihat status pembayaran per penerima
- Bisa lihat total outstanding per supplier

### 2. **Fleksibilitas Pembayaran**
- DP bisa dibayar di awal
- OA bisa dibayar per penerima (tidak harus sekaligus)
- Bisa bayar sebagian (partial payment)

### 3. **Laporan Akurat**
- Total tagihan = DP + Σ OA semua penerima
- Total dibayar = DP + Σ OA yang sudah dibayar
- Sisa tagihan = Total tagihan - Total dibayar

### 4. **Integrasi dengan Halaman Pembayaran**
- Halaman pembayaran supplier bisa filter by tipe (DP atau OA)
- Bisa lihat detail pembayaran per penerima
- Bisa track pembayaran yang belum lunas

## Relasi di Model

### OaPayment Model:
```php
// Relasi ke penerima (untuk tipe 'oa')
public function penerima(): BelongsTo
{
    return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
}

// Relasi ke kendaraan (untuk tipe 'dp_supplier')
public function kendaraan(): BelongsTo
{
    return $this->belongsTo(PoKendaraan::class, 'po_kendaraan_id');
}

// Relasi ke supplier
public function supplier(): BelongsTo
{
    return $this->belongsTo(Supplier::class, 'supplier_id');
}
```

### PoPenerima Model:
```php
// Relasi ke pembayaran OA
public function oaPayment(): HasOne
{
    return $this->hasOne(OaPayment::class, 'po_penerima_id')
        ->where('tipe_pembayaran', 'oa');
}
```

### PoKendaraan Model:
```php
// Relasi ke pembayaran DP
public function dpPayment(): HasOne
{
    return $this->hasOne(OaPayment::class, 'po_kendaraan_id')
        ->where('tipe_pembayaran', 'dp_supplier');
}
```

## Query untuk Laporan

### Total Tagihan Supplier per PO:
```php
$totalTagihan = OaPayment::whereHas('penerima.kendaraan.po', fn($q) => $q->where('id', $poId))
    ->orWhereHas('kendaraan.po', fn($q) => $q->where('id', $poId))
    ->sum('jumlah_tagihan');
```

### Total Dibayar per Supplier:
```php
$totalDibayar = OaPayment::where('supplier_id', $supplierId)
    ->sum('jumlah_bayar');
```

### Outstanding Payment per Supplier:
```php
$outstanding = OaPayment::where('supplier_id', $supplierId)
    ->whereIn('status', ['pending', 'partial'])
    ->selectRaw('SUM(jumlah_tagihan - jumlah_bayar) as sisa')
    ->value('sisa');
```

## Testing

### Test Case 1: Create PO dengan DP
1. Create PO dengan DP Rp 2.000.000
2. Tambah 2 penerima dengan total OA masing-masing Rp 5.000.000
3. Verifikasi di `oa_payments`:
   - ✅ 1 record DP (tipe: dp_supplier, jumlah_bayar: 2.000.000, status: partial)
   - ✅ 2 record OA (tipe: oa, jumlah_tagihan: 5.000.000 each, status: pending)

### Test Case 2: Create PO tanpa DP
1. Create PO tanpa DP
2. Tambah 1 penerima dengan total OA Rp 3.000.000
3. Verifikasi di `oa_payments`:
   - ✅ 0 record DP
   - ✅ 1 record OA (tipe: oa, jumlah_tagihan: 3.000.000, status: pending)

### Test Case 3: Halaman Pembayaran Supplier
1. Buka `/keuangan/pembayaran`
2. Filter tipe: "DP Supplier"
3. Verifikasi:
   - ✅ Hanya tampil record DP
   - ✅ Kolom tujuan tampil "—"
4. Filter tipe: "Pembayaran OA"
5. Verifikasi:
   - ✅ Hanya tampil record OA
   - ✅ Kolom tujuan tampil nama tujuan

## File yang Dimodifikasi

1. ✅ `app/Http/Controllers/PurchaseOrderController.php`
   - Method `store()`: Tambah logika create OaPayment per penerima

## Catatan Penting

1. **DP vs OA:**
   - DP: Dibayar di awal, tidak ada tagihan, langsung bayar
   - OA: Ada tagihan, dibayar setelah barang sampai

2. **Status Pembayaran:**
   - `pending`: Belum dibayar sama sekali
   - `partial`: Sudah dibayar sebagian
   - `lunas`: Sudah dibayar penuh

3. **Relasi:**
   - DP: Terkait ke `po_kendaraan_id`
   - OA: Terkait ke `po_penerima_id`

4. **Perhitungan Total:**
   - Total Tagihan Supplier = Σ OA semua penerima (DP tidak masuk tagihan)
   - Total Dibayar = DP + Σ OA yang sudah dibayar
   - Sisa Tagihan = Total Tagihan - (Total Dibayar - DP)

## Progress Fitur

### ✅ Sudah Selesai (98%):
1. ✅ Database migration
2. ✅ Model accessor methods
3. ✅ Controller validation & save
4. ✅ Create PO form dengan format Rupiah
5. ✅ JavaScript auto-calculate & format
6. ✅ Show PO dengan informasi DP
7. ✅ Integrasi dengan OA Payments
8. ✅ Halaman pembayaran supplier dengan filter tipe
9. ✅ **Auto-create OA Payment per penerima** ← NEW

### 🔄 Belum Selesai (2%):
1. ⏳ Edit PO form untuk edit data DP
2. ⏳ Export PDF dengan info DP

### 📊 Progress: 98% Complete! 🎉🎉
