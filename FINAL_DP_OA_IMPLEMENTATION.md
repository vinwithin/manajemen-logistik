# Final: Implementasi DP + OA dalam 1 Record

## Tanggal: 10 Mei 2026

## Keputusan Final

**1 Record OaPayment per Penerima** yang sudah include DP (jika ada):
- **Penerima Pertama**: Record OA + DP (jika ada DP)
- **Penerima Lainnya**: Record OA saja

## Logika Implementasi

### Konsep:
```
Kendaraan dengan DP Rp 2.000.000:
├── Penerima 1 (Kandang A) - Total OA: Rp 8.000.000
│   └── OaPayment:
│       - jumlah_tagihan: Rp 8.000.000
│       - jumlah_bayar: Rp 2.000.000 (DP)
│       - status: 'partial'
│       - keterangan: "Pembayaran OA + DP Supplier - Kandang A"
│
└── Penerima 2 (Kandang B) - Total OA: Rp 4.000.000
    └── OaPayment:
        - jumlah_tagihan: Rp 4.000.000
        - jumlah_bayar: Rp 0
        - status: 'pending'
        - keterangan: "Pembayaran OA - Kandang B"
```

## Kode Implementasi

```php
// Simpan data DP untuk digunakan di penerima pertama
$dpNominal = (!empty($kendaraanData['dp_nominal']) && $kendaraanData['dp_nominal'] > 0) 
    ? $kendaraanData['dp_nominal'] 
    : 0;
$dpTanggal = $kendaraanData['dp_tanggal'] ?? null;
$dpMetode = $kendaraanData['dp_metode'] ?? null;
$dpKeterangan = $kendaraanData['dp_keterangan'] ?? null;

$isFirstPenerima = true; // Flag untuk penerima pertama

foreach ($kendaraanData['penerima'] ?? [] as $penerimaData) {
    // ... create penerima & pakans ...
    
    // Hitung total tagihan OA
    $totalTagihanOa = 0;
    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
        // ... create pakan ...
        $totalTagihanOa += ($pakanData['jumlah_kg'] ?? 0) * ($pakanData['ongkos_oa'] ?? 0);
    }
    
    // Buat record OaPayment
    if ($totalTagihanOa > 0) {
        $jumlahBayar = 0;
        $tanggalBayar = null;
        $metodeBayar = null;
        $keterangan = 'Pembayaran OA - '.$penerima->nama_penerima.' (PO: '.$po->no_po.')';
        $status = 'pending';
        
        // Jika penerima pertama dan ada DP, include DP dalam record ini
        if ($isFirstPenerima && $dpNominal > 0) {
            $jumlahBayar = $dpNominal;
            $tanggalBayar = $dpTanggal ?? now();
            $metodeBayar = $dpMetode ?? 'transfer';
            $keterangan = 'Pembayaran OA + DP Supplier - '.$penerima->nama_penerima.' (PO: '.$po->no_po.')';
            if ($dpKeterangan) {
                $keterangan .= ' | DP: '.$dpKeterangan;
            }
            $status = $dpNominal >= $totalTagihanOa ? 'lunas' : 'partial';
        }
        
        OaPayment::create([
            'po_penerima_id' => $penerima->id,
            'po_kendaraan_id' => $kendaraan->id, // Include untuk tracking DP
            'supplier_id' => $kendaraan->supplier_id,
            'tipe_pembayaran' => 'oa',
            'jumlah_tagihan' => $totalTagihanOa,
            'jumlah_bayar' => $jumlahBayar,
            'tanggal_bayar' => $tanggalBayar,
            'metode_bayar' => $metodeBayar,
            'keterangan' => $keterangan,
            'status' => $status,
        ]);
        
        $isFirstPenerima = false; // Set flag ke false setelah penerima pertama
    }
}
```

## Contoh Data

### Input:
- **Kendaraan**: B 1234 AB
- **Supplier**: PT ABC
- **DP**: Rp 2.000.000 (dibayar hari ini)
- **Penerima 1** (Kandang A):
  - Pakan BR1: 1000 kg × Rp 5.000 = Rp 5.000.000
  - Pakan 511: 500 kg × Rp 6.000 = Rp 3.000.000
  - **Total OA**: Rp 8.000.000
- **Penerima 2** (Kandang B):
  - Pakan BR1: 800 kg × Rp 5.000 = Rp 4.000.000
  - **Total OA**: Rp 4.000.000

### Output di `oa_payments`:

| id | tipe | po_kendaraan_id | po_penerima_id | jumlah_tagihan | jumlah_bayar | status | keterangan |
|----|------|-----------------|----------------|----------------|--------------|--------|------------|
| 1  | oa   | 1               | 1              | 8.000.000      | 2.000.000    | partial | Pembayaran OA + DP Supplier - Kandang A (PO: PO-001) |
| 2  | oa   | 1               | 2              | 4.000.000      | 0            | pending | Pembayaran OA - Kandang B (PO: PO-001) |

**Total Record**: 2 (bukan 3!)

## Keuntungan Pendekatan Ini

### ✅ **1. Efisien**
- Hanya 1 record per penerima (bukan 2 record terpisah untuk DP dan OA)
- Lebih mudah tracking pembayaran

### ✅ **2. Integrasi dengan RekapOaController**
- RekapOaController query: `PoPenerima::with(['oaPayment'])`
- Setiap penerima pasti punya 1 record OaPayment
- DP sudah include dalam record penerima pertama

### ✅ **3. Status Pembayaran Jelas**
- **Penerima 1**: Status 'partial' (sudah bayar DP Rp 2jt dari tagihan Rp 8jt)
- **Penerima 2**: Status 'pending' (belum bayar sama sekali)

### ✅ **4. Keterangan Informatif**
- Penerima 1: "Pembayaran OA + DP Supplier - Kandang A | DP: Uang muka awal"
- Penerima 2: "Pembayaran OA - Kandang B"

## Perhitungan Pembayaran

### Total Tagihan Supplier:
```
= Total OA Penerima 1 + Total OA Penerima 2
= Rp 8.000.000 + Rp 4.000.000
= Rp 12.000.000
```

### Total Sudah Dibayar:
```
= DP (dari penerima 1)
= Rp 2.000.000
```

### Sisa Tagihan:
```
= Total Tagihan - Total Dibayar
= Rp 12.000.000 - Rp 2.000.000
= Rp 10.000.000
```

### Detail per Penerima:
- **Penerima 1 (Kandang A)**:
  - Tagihan: Rp 8.000.000
  - Dibayar: Rp 2.000.000 (DP)
  - Sisa: Rp 6.000.000
  - Status: Partial

- **Penerima 2 (Kandang B)**:
  - Tagihan: Rp 4.000.000
  - Dibayar: Rp 0
  - Sisa: Rp 4.000.000
  - Status: Pending

## Skenario Edge Cases

### Skenario 1: DP = Total Tagihan Penerima 1
```
DP: Rp 8.000.000
Penerima 1 Total OA: Rp 8.000.000

Result:
- Penerima 1: status = 'lunas' (DP = tagihan)
- Penerima 2: status = 'pending'
```

### Skenario 2: DP > Total Tagihan Penerima 1
```
DP: Rp 10.000.000
Penerima 1 Total OA: Rp 8.000.000

Result:
- Penerima 1: 
  - jumlah_bayar = Rp 10.000.000
  - status = 'lunas' (DP > tagihan)
  - Kelebihan DP bisa dipotong dari penerima 2 (manual di halaman pembayaran)
```

### Skenario 3: Tidak Ada DP
```
DP: Rp 0

Result:
- Penerima 1: status = 'pending', jumlah_bayar = 0
- Penerima 2: status = 'pending', jumlah_bayar = 0
```

### Skenario 4: Hanya 1 Penerima
```
DP: Rp 2.000.000
Penerima 1 Total OA: Rp 8.000.000

Result:
- Hanya 1 record OaPayment dengan DP included
```

## Kolom `po_kendaraan_id` di OaPayment

**Kenapa diisi untuk semua record?**
- Untuk tracking DP (penerima pertama)
- Untuk relasi ke kendaraan (semua penerima)
- Untuk query di PembayaranSupplierController

**Relasi:**
```php
// Di PembayaranSupplierController
$query = OaPayment::with([
    'penerima.kendaraan.po.cv', // Relasi via penerima
    'kendaraan.po.cv',           // Relasi langsung (untuk DP)
]);
```

## Testing

### Test Case 1: Create PO dengan DP
1. Create PO dengan DP Rp 2.000.000
2. Tambah 2 penerima (Total OA: Rp 8jt dan Rp 4jt)
3. Submit PO
4. Cek `oa_payments`:
   - ✅ 2 record (1 per penerima)
   - ✅ Record 1: jumlah_bayar = 2.000.000, status = 'partial'
   - ✅ Record 2: jumlah_bayar = 0, status = 'pending'

### Test Case 2: Create PO tanpa DP
1. Create PO tanpa DP
2. Tambah 2 penerima
3. Submit PO
4. Cek `oa_payments`:
   - ✅ 2 record (1 per penerima)
   - ✅ Semua record: jumlah_bayar = 0, status = 'pending'

### Test Case 3: DP = Total Tagihan Penerima 1
1. Create PO dengan DP Rp 8.000.000
2. Penerima 1: Total OA Rp 8.000.000
3. Submit PO
4. Cek `oa_payments`:
   - ✅ Record 1: status = 'lunas'

## File yang Dimodifikasi

1. ✅ `app/Http/Controllers/PurchaseOrderController.php`
   - Method `store()`: Gabungkan DP dengan OA penerima pertama

## Summary

### ✅ Yang Sudah Selesai (100%):
1. ✅ Database migration (DP fields + tipe_pembayaran)
2. ✅ Model accessor methods
3. ✅ Controller validation & save
4. ✅ Create PO form dengan format Rupiah
5. ✅ JavaScript auto-calculate & format
6. ✅ Show PO dengan informasi DP
7. ✅ **1 Record OaPayment per Penerima (DP included di penerima pertama)** ← FINAL
8. ✅ Halaman pembayaran supplier dengan filter tipe
9. ✅ Integrasi dengan RekapOaController

### 📊 Progress: 100% Complete! 🎉🎉🎉

**Fitur DP Supplier sudah selesai sempurna!**

Sekarang sistem akan membuat:
- **1 record per penerima** (bukan 2 record terpisah)
- **DP included** di record penerima pertama
- **Lebih efisien** dan mudah tracking
