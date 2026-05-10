# Perbaikan Halaman Pembayaran Supplier

## Tanggal: 10 Mei 2026

## Masalah
Relasi di halaman pembayaran supplier tidak benar. Controller menggunakan relasi `item.po` yang sudah tidak digunakan lagi, seharusnya menggunakan relasi yang sesuai dengan struktur baru:
- Untuk pembayaran OA: `penerima` → `kendaraan` → `po`
- Untuk pembayaran DP Supplier: `kendaraan` → `po`

## Solusi

### 1. Update Controller: `PembayaranSupplierController.php`

#### Perubahan Query & Relasi:
```php
// BEFORE (SALAH):
$query = OaPayment::with(['supplier', 'item.po.cv', 'item.tujuan'])
    ->when($activeCvId, fn ($q) => $q->whereHas('item.po', fn ($q) => $q->where('cv_id', $activeCvId)));

// AFTER (BENAR):
$query = OaPayment::with([
    'supplier',
    'penerima.kendaraan.po.cv', // Untuk tipe 'oa'
    'penerima.tujuan',
    'kendaraan.po.cv', // Untuk tipe 'dp_supplier'
])
    ->when($activeCvId, function ($q) use ($activeCvId) {
        $q->where(function ($q) use ($activeCvId) {
            $q->whereHas('penerima.kendaraan.po', fn ($q) => $q->where('cv_id', $activeCvId))
              ->orWhereHas('kendaraan.po', fn ($q) => $q->where('cv_id', $activeCvId));
        });
    });
```

#### Perubahan DataTables Columns:

**1. Tambah Kolom Tipe Pembayaran:**
```php
->addColumn('tipe', function ($q) {
    $badges = [
        'oa' => ['info', 'Pembayaran OA'],
        'dp_supplier' => ['warning', 'DP Supplier'],
    ];
    [$color, $label] = $badges[$q->tipe_pembayaran] ?? ['secondary', $q->tipe_pembayaran];
    return "<span class='badge bg-{$color}'>{$label}</span>";
})
```

**2. Perbaiki Kolom No PO:**
```php
// BEFORE (SALAH):
->addColumn('no_po', fn ($q) => $q->po->no_po ?? '-')

// AFTER (BENAR):
->addColumn('no_po', function ($q) {
    if ($q->tipe_pembayaran === 'dp_supplier' && $q->kendaraan) {
        return $q->kendaraan->po->no_po ?? '-';
    }
    return $q->penerima?->kendaraan?->po->no_po ?? '-';
})
```

**3. Perbaiki Kolom CV:**
```php
// BEFORE (SALAH):
->addColumn('cv_name', fn ($q) => $q->item->po->cv?->nama_cv ?? '-')

// AFTER (BENAR):
->addColumn('cv_name', function ($q) {
    if ($q->tipe_pembayaran === 'dp_supplier' && $q->kendaraan) {
        return $q->kendaraan->po->cv?->nama_cv ?? '-';
    }
    return $q->penerima?->kendaraan?->po->cv?->nama_cv ?? '-';
})
```

**4. Perbaiki Kolom No Polisi:**
```php
// BEFORE (SALAH):
->addColumn('no_polisi', fn ($q) => $q->item->no_polisi ?? '-')

// AFTER (BENAR):
->addColumn('no_polisi', function ($q) {
    if ($q->tipe_pembayaran === 'dp_supplier' && $q->kendaraan) {
        return $q->kendaraan->no_polisi ?? '-';
    }
    return $q->penerima?->kendaraan?->no_polisi ?? '-';
})
```

**5. Perbaiki Kolom Tujuan:**
```php
// BEFORE (SALAH):
->addColumn('tujuan', fn ($q) => $q->item->tujuan?->nama ?? '-')

// AFTER (BENAR):
->addColumn('tujuan', function ($q) {
    // Tujuan hanya ada untuk tipe 'oa' (pembayaran ke penerima)
    if ($q->tipe_pembayaran === 'oa') {
        return $q->penerima?->tujuan?->nama ?? '-';
    }
    return '<span class="text-muted">—</span>';
})
```

**6. Tambah Format Tanggal & Metode:**
```php
->editColumn('tanggal_bayar', fn ($q) => $q->tanggal_bayar ? $q->tanggal_bayar->format('d/m/Y') : '-')
->editColumn('metode_bayar', fn ($q) => $q->metode_bayar ? ucfirst($q->metode_bayar) : '-')
```

#### Perubahan Summary:
```php
// Tambah summary untuk DP
'count_oa' => (clone $base)->where('tipe_pembayaran', 'oa')->count(),
'count_dp' => (clone $base)->where('tipe_pembayaran', 'dp_supplier')->count(),
'total_dp' => (clone $base)->where('tipe_pembayaran', 'dp_supplier')->sum('jumlah_bayar'),
```

### 2. Update View: `resources/views/pages/keuangan/pembayaran/index.blade.php`

#### Tambah Summary Card untuk DP:
```blade
<div class="col-6 col-md-2">
    <div class="card text-center py-3">
        <div class="fw-bold fs-5 text-warning">
            Rp {{ number_format($summary['total_dp'], 0, ',', '.') }}
        </div>
        <div class="text-muted small">Total DP Supplier</div>
    </div>
</div>
<div class="col-6 col-md-2">
    <div class="card text-center py-3">
        <div class="fw-bold fs-5">
            <span class="text-info">{{ $summary['count_oa'] }}</span> /
            <span class="text-warning">{{ $summary['count_dp'] }}</span>
        </div>
        <div class="text-muted small">OA / DP</div>
    </div>
</div>
```

#### Tambah Filter Tipe Pembayaran:
```blade
<select id="filterTipe" class="form-select form-select-sm" style="width:150px">
    <option value="">Semua Tipe</option>
    <option value="oa">Pembayaran OA</option>
    <option value="dp_supplier">DP Supplier</option>
</select>
```

#### Tambah Kolom Tipe di Tabel:
```blade
<thead>
    <tr>
        <th>No</th>
        <th>Tipe</th>  <!-- KOLOM BARU -->
        <th>No. PO</th>
        <!-- ... kolom lainnya -->
    </tr>
</thead>
```

#### Update DataTables JavaScript:
```javascript
ajax: {
    url: '/keuangan/pembayaran',
    data: function(d) {
        d.tipe_pembayaran = $('#filterTipe').val(); // TAMBAH FILTER TIPE
        d.supplier_id = $('#filterSupplier').val();
        d.status = $('#filterStatus').val();
        d.from = $('#filterFrom').val();
        d.to = $('#filterTo').val();
    }
},
columns: [
    { data: 'DT_RowIndex', ... },
    { data: 'tipe', name: 'tipe_pembayaran', searchable: false }, // KOLOM BARU
    { data: 'no_po', name: 'no_po' },
    // ... kolom lainnya
]
```

## Struktur Data OA Payments

### Tipe Pembayaran:

#### 1. `tipe_pembayaran = 'oa'` (Pembayaran OA ke Penerima)
- **Relasi**: `penerima` → `kendaraan` → `po`
- **Data**:
  - `po_penerima_id`: ID penerima
  - `po_kendaraan_id`: NULL
  - Tujuan: Ada (dari penerima)
  - No Polisi: Dari kendaraan penerima

#### 2. `tipe_pembayaran = 'dp_supplier'` (Down Payment ke Supplier)
- **Relasi**: `kendaraan` → `po`
- **Data**:
  - `po_penerima_id`: NULL
  - `po_kendaraan_id`: ID kendaraan
  - Tujuan: Tidak ada (tampilkan "—")
  - No Polisi: Dari kendaraan langsung

## Testing

### Test Case 1: Pembayaran OA
1. Buka halaman `/keuangan/pembayaran`
2. Filter tipe: "Pembayaran OA"
3. Verifikasi:
   - ✅ No PO tampil dengan benar
   - ✅ CV tampil dengan benar
   - ✅ No Polisi tampil dengan benar
   - ✅ Tujuan tampil dengan benar
   - ✅ Badge "Pembayaran OA" berwarna biru (info)

### Test Case 2: DP Supplier
1. Buka halaman `/keuangan/pembayaran`
2. Filter tipe: "DP Supplier"
3. Verifikasi:
   - ✅ No PO tampil dengan benar
   - ✅ CV tampil dengan benar
   - ✅ No Polisi tampil dengan benar
   - ✅ Tujuan tampil "—" (tidak ada tujuan untuk DP)
   - ✅ Badge "DP Supplier" berwarna kuning (warning)

### Test Case 3: Summary Cards
1. Verifikasi summary cards menampilkan:
   - ✅ Total Tagihan
   - ✅ Total Dibayar
   - ✅ Sisa Tagihan
   - ✅ Count Pending/Partial/Lunas
   - ✅ Total DP Supplier
   - ✅ Count OA / DP

### Test Case 4: Filter
1. Test filter tipe pembayaran
2. Test filter supplier
3. Test filter status
4. Test filter tanggal (dari - sampai)
5. Test kombinasi filter

## File yang Dimodifikasi

1. ✅ `app/Http/Controllers/PembayaranSupplierController.php`
   - Perbaiki relasi query
   - Perbaiki DataTables columns
   - Tambah filter tipe pembayaran
   - Tambah summary DP
   - Tambah format tanggal dan metode

2. ✅ `resources/views/pages/keuangan/pembayaran/index.blade.php`
   - Tambah summary card DP
   - Tambah filter tipe pembayaran
   - Tambah kolom tipe di tabel
   - Update DataTables JavaScript

## Catatan Penting

1. **Relasi Berbeda untuk Setiap Tipe:**
   - Pembayaran OA: Melalui `penerima` → `kendaraan` → `po`
   - DP Supplier: Langsung melalui `kendaraan` → `po`

2. **Tujuan Hanya untuk OA:**
   - Pembayaran OA: Tampilkan tujuan dari penerima
   - DP Supplier: Tampilkan "—" karena tidak ada tujuan

3. **Filter CV:**
   - Harus menggunakan `orWhereHas` untuk kedua tipe pembayaran
   - Tidak bisa hanya filter satu relasi saja

4. **Format Data:**
   - Tanggal: Format Indonesia (d/m/Y)
   - Metode: Capitalize first letter
   - Rupiah: Format dengan pemisah ribuan

## Hasil

Halaman pembayaran supplier sekarang:
- ✅ Menampilkan data dengan relasi yang benar
- ✅ Mendukung 2 tipe pembayaran (OA dan DP Supplier)
- ✅ Filter tipe pembayaran berfungsi
- ✅ Summary cards include data DP
- ✅ Kolom tujuan conditional (hanya untuk OA)
- ✅ Format tanggal dan metode yang rapi
- ✅ Badge warna berbeda untuk setiap tipe

## Progress Fitur DP Supplier

Dengan perbaikan ini, progress fitur DP Supplier menjadi:

### ✅ Sudah Selesai (95%):
1. ✅ Database migration
2. ✅ Model accessor methods
3. ✅ Controller validation & save
4. ✅ Create PO form dengan format Rupiah
5. ✅ JavaScript auto-calculate & format
6. ✅ Show PO dengan informasi DP
7. ✅ Integrasi dengan OA Payments
8. ✅ **Halaman pembayaran supplier dengan filter tipe** ← NEW

### 🔄 Belum Selesai (5%):
1. ⏳ Edit PO form untuk edit data DP
2. ⏳ Export PDF dengan info DP
3. ⏳ Dashboard widget monitoring DP

### 📊 Progress: 95% Complete! 🎉
