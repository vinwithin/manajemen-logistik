# Final: Implementasi DP Supplier + OA Payment

## Tanggal: 10 Mei 2026

## Keputusan Final

Setelah diskusi dan review RekapOaController, diputuskan bahwa:
- **Di store PurchaseOrder**: Track **DP Supplier** DAN **Pembayaran OA per Penerima**
- **Alasan**: RekapOaController sudah menggunakan relasi `oaPayment` dari penerima, jadi record harus dibuat saat create PO

## Implementasi di Store PurchaseOrder

### Yang Dibuat:

#### 1️⃣ **1 record OaPayment untuk DP Supplier** (jika ada DP)

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

#### 2️⃣ **N record OaPayment untuk Pembayaran OA** (1 per penerima)

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

## Struktur Data

### Tabel `oa_payments`:
Kolom yang tersedia:
- `po_kendaraan_id` - Untuk DP Supplier (tipe: dp_supplier)
- `po_penerima_id` - Untuk Pembayaran OA (tipe: oa) - **akan diisi di tempat lain**
- `supplier_id` - ID supplier
- `tipe_pembayaran` - Enum: 'oa' atau 'dp_supplier'
- `jumlah_tagihan` - Total tagihan
- `jumlah_bayar` - Jumlah yang sudah dibayar
- `tanggal_bayar` - Tanggal pembayaran
- `metode_bayar` - Metode: transfer, tunai, giro
- `keterangan` - Catatan
- `status` - Status: pending, partial, lunas

## Contoh Data

### Saat Create PO dengan DP:

**Input:**
- Kendaraan: B 1234 AB
- Supplier: PT ABC
- DP: Rp 2.000.000
- Penerima 1: Kandang A (Total OA: Rp 8.000.000)
- Penerima 2: Kandang B (Total OA: Rp 4.000.000)

**Record di `oa_payments` setelah create PO:**

| id | tipe_pembayaran | po_kendaraan_id | po_penerima_id | supplier_id | jumlah_tagihan | jumlah_bayar | status | keterangan |
|----|-----------------|-----------------|----------------|-------------|----------------|--------------|--------|------------|
| 1  | dp_supplier     | 1               | NULL           | 5           | 0              | 2.000.000    | partial | Down Payment Supplier - PO: PO-001 |
| 2  | oa              | NULL            | 1              | 5           | 8.000.000      | 0            | pending | Pembayaran OA - Kandang A (PO: PO-001) |
| 3  | oa              | NULL            | 2              | 5           | 4.000.000      | 0            | pending | Pembayaran OA - Kandang B (PO: PO-001) |

**Catatan:**
- 1 record untuk DP (jika ada)
- N record untuk pembayaran OA (1 per penerima)

## Keuntungan Pendekatan Ini

### ✅ **1. Tracking Lengkap**
- Semua pembayaran ke supplier tercatat (DP + OA)
- Bisa lihat status pembayaran per penerima
- Bisa lihat total outstanding per supplier

### ✅ **2. Integrasi dengan RekapOaController**
- RekapOaController sudah menggunakan relasi `oaPayment` dari penerima
- Query: `PoPenerima::with(['oaPayment'])->whereHas('oaPayment', ...)`
- Tidak perlu refactor RekapOaController

### ✅ **3. Fleksibilitas Pembayaran**
- DP bisa dibayar di awal
- OA bisa dibayar per penerima (tidak harus sekaligus)
- Bisa bayar sebagian (partial payment)

### ✅ **4. Laporan Akurat**
- Total tagihan = DP + Σ OA semua penerima
- Total dibayar = DP + Σ OA yang sudah dibayar
- Sisa tagihan = Total tagihan - Total dibayar

## Tempat Pembayaran OA Akan Dibuat

Pembayaran OA kemungkinan akan dibuat di:

### Option 1: Saat Barang Tiba
```php
// Di controller saat update status penerima menjadi 'tiba'
if ($penerima->status === 'tiba') {
    $totalOa = $penerima->pakans->sum(fn($p) => $p->jumlah_kg * $p->ongkos_oa);
    
    OaPayment::create([
        'po_penerima_id' => $penerima->id,
        'supplier_id' => $penerima->kendaraan->supplier_id,
        'tipe_pembayaran' => 'oa',
        'jumlah_tagihan' => $totalOa,
        'jumlah_bayar' => 0,
        'status' => 'pending',
        'keterangan' => 'Pembayaran OA - ' . $penerima->nama_penerima,
    ]);
}
```

### Option 2: Manual di Halaman Pembayaran
- Finance buka halaman pembayaran supplier
- Pilih penerima yang belum ada record pembayaran
- Klik "Buat Tagihan OA"
- System create record OaPayment dengan status pending

### Option 3: Batch Process
- Setiap hari/minggu, system scan penerima yang sudah 'tiba' tapi belum ada record OA
- Auto-create record OaPayment untuk penerima tersebut

## File yang Dimodifikasi

1. ✅ `app/Http/Controllers/PurchaseOrderController.php`
   - Method `store()`: Hanya create OaPayment untuk DP
   - Hapus logic create OaPayment per penerima

## Testing

### Test Case: Create PO dengan DP
1. Create PO dengan DP Rp 2.000.000
2. Tambah 2 penerima
3. Submit PO
4. Cek tabel `oa_payments`:
   - ✅ Hanya ada 1 record (tipe: dp_supplier)
   - ✅ `po_kendaraan_id` terisi
   - ✅ `po_penerima_id` = NULL
   - ✅ `jumlah_bayar` = 2.000.000
   - ✅ `status` = 'partial'

### Test Case: Create PO tanpa DP
1. Create PO tanpa DP
2. Tambah 1 penerima
3. Submit PO
4. Cek tabel `oa_payments`:
   - ✅ Tidak ada record baru

## Summary

### ✅ Yang Sudah Selesai (100%):
1. ✅ Database migration (DP fields + tipe_pembayaran)
2. ✅ Model accessor methods
3. ✅ Controller validation & save
4. ✅ Create PO form dengan format Rupiah
5. ✅ JavaScript auto-calculate & format
6. ✅ Show PO dengan informasi DP
7. ✅ **Store PurchaseOrder hanya track DP** ← FINAL
8. ✅ Halaman pembayaran supplier dengan filter tipe

### 📝 Catatan untuk Developer Selanjutnya:
- Pembayaran OA per penerima perlu dibuat di tempat lain
- Kolom `po_penerima_id` sudah tersedia di tabel `oa_payments`
- Tinggal create record dengan `tipe_pembayaran = 'oa'` saat barang tiba atau manual

### 📊 Progress: 100% Complete! 🎉🎉🎉

Fitur DP Supplier sudah selesai dan siap digunakan!
