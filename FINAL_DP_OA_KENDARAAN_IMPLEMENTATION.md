# Implementasi Final: DP & OA Payment per Kendaraan

## Overview
Implementasi fitur Down Payment (DP) Supplier dan pembayaran OA (Ongkos Angkut) dengan pendekatan **1 record OaPayment per kendaraan** (bukan per penerima).

## Perubahan Utama

### 1. Database Structure
- **Tabel `po_kendaraan`**: Menyimpan data DP
  - `dp_nominal` (decimal)
  - `dp_persen` (decimal)
  - `dp_tanggal` (date)
  - `dp_metode` (enum: transfer, tunai, giro)
  - `dp_keterangan` (text)

- **Tabel `oa_payments`**: Menyimpan pembayaran OA + DP
  - `po_kendaraan_id` (foreign key ke po_kendaraan)
  - `po_penerima_id` (nullable - NULL untuk payment per kendaraan)
  - `tipe_pembayaran` (enum: 'oa', 'dp_supplier')
  - `jumlah_tagihan` (total OA dari semua penerima di kendaraan)
  - `jumlah_bayar` (termasuk DP jika ada)
  - `status` (pending, partial, lunas)

### 2. Model Relations

#### PoKendaraan Model
```php
// Pembayaran OA untuk kendaraan ini (termasuk DP jika ada)
public function oaPayment()
{
    return $this->hasOne(OaPayment::class, 'po_kendaraan_id')
        ->where('tipe_pembayaran', 'oa');
}

// Accessor methods
public function getTotalTagihanSupplierAttribute(): float
{
    return $this->penerimas->sum(function ($penerima) {
        return $penerima->pakans->sum(function ($pakan) {
            return $pakan->jumlah_kg * ($pakan->ongkos_oa ?? 0);
        });
    });
}

public function getSisaTagihanAttribute(): float
{
    return max(0, $this->total_tagihan_supplier - $this->dp_nominal);
}
```

#### OaPayment Model
```php
public function kendaraan(): BelongsTo
{
    return $this->belongsTo(PoKendaraan::class, 'po_kendaraan_id');
}

public function penerima(): BelongsTo
{
    return $this->belongsTo(PoPenerima::class, 'po_penerima_id');
}
```

### 3. Controller Logic

#### PurchaseOrderController::store()
**Logika Baru:**
1. Loop semua penerima di kendaraan
2. Hitung total tagihan OA dari semua penerima
3. **Setelah semua penerima dibuat**, buat **1 record OaPayment** per kendaraan dengan:
   - `po_kendaraan_id` = ID kendaraan
   - `po_penerima_id` = NULL
   - `jumlah_tagihan` = total OA dari semua penerima
   - `jumlah_bayar` = DP nominal (jika ada)
   - `tipe_pembayaran` = 'oa'
   - `status` = 'pending' / 'partial' / 'lunas' (tergantung DP)

```php
// Variabel untuk menghitung total tagihan OA dari semua penerima di kendaraan ini
$totalTagihanKendaraan = 0;

foreach ($kendaraanData['penerima'] ?? [] as $penerimaData) {
    // Create penerima...
    
    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
        // Create pakan...
        
        // Akumulasi total tagihan OA untuk kendaraan
        $totalTagihanKendaraan += ($pakanData['jumlah_kg'] ?? 0) * ($pakanData['ongkos_oa'] ?? 0);
    }
}

// Buat 1 record OaPayment per kendaraan (bukan per penerima)
if ($totalTagihanKendaraan > 0) {
    $dpNominal = $kendaraanData['dp_nominal'] ?? 0;
    
    OaPayment::create([
        'po_kendaraan_id' => $kendaraan->id,
        'po_penerima_id' => null, // NULL karena ini per kendaraan
        'supplier_id' => $kendaraan->supplier_id,
        'tipe_pembayaran' => 'oa',
        'jumlah_tagihan' => $totalTagihanKendaraan,
        'jumlah_bayar' => $dpNominal,
        'tanggal_bayar' => $dpNominal > 0 ? ($dpTanggal ?? now()) : null,
        'metode_bayar' => $dpNominal > 0 ? ($dpMetode ?? 'transfer') : null,
        'keterangan' => 'Pembayaran OA - Kendaraan '.$kendaraan->no_polisi,
        'status' => $dpNominal >= $totalTagihanKendaraan ? 'lunas' : ($dpNominal > 0 ? 'partial' : 'pending'),
    ]);
}
```

#### RekapOaController
**Perubahan:**
- Query dari `PoPenerima` → `PoKendaraan`
- Filter dan relasi disesuaikan
- DataTables columns disesuaikan

```php
public function index(Request $request)
{
    $query = \App\Models\PoKendaraan::with(['po.cv', 'supplier', 'penerimas', 'oaPayment'])
        ->whereIn('status', ['selesai', 'batal'])
        ->whereHas('po', function ($q) use ($activeCvId, $from, $to) {
            // Filter CV, tanggal
        })
        ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
        ->when($status, function ($q) use ($status) {
            if ($status === 'pending') {
                $q->whereDoesntHave('oaPayment')
                    ->orWhereHas('oaPayment', fn ($q2) => $q2->where('status', 'pending'));
            } else {
                $q->whereHas('oaPayment', fn ($q2) => $q2->where('status', $status));
            }
        });
    
    return DataTables::of($query)
        ->addColumn('penerima_list', fn ($q) => $q->penerimas->pluck('nama_penerima')->join(', '))
        ->addColumn('dp_nominal', fn ($q) => number_format($q->dp_nominal ?? 0, 0, ',', '.'))
        // ... columns lainnya
}

public function bayar(string $id)
{
    $kendaraan = \App\Models\PoKendaraan::with([...])
        ->findOrFail(decrypt($id));
    
    $tagihan = $kendaraan->total_oa;
    
    return view('pages.keuangan.oa.bayar', compact('kendaraan', 'tagihan'));
}

public function storeBayar(Request $request, string $id)
{
    $kendaraan = \App\Models\PoKendaraan::findOrFail(decrypt($id));
    
    $existing = OaPayment::where('po_kendaraan_id', $kendaraan->id)
        ->where('tipe_pembayaran', 'oa')
        ->first();
    
    $totalBayar = ($existing?->jumlah_bayar ?? 0) + $request->jumlah_bayar;
    
    OaPayment::updateOrCreate(
        [
            'po_kendaraan_id' => $kendaraan->id,
            'tipe_pembayaran' => 'oa',
        ],
        [
            'po_penerima_id' => null,
            'supplier_id' => $kendaraan->supplier_id,
            'jumlah_tagihan' => $kendaraan->total_oa,
            'jumlah_bayar' => $totalBayar,
            // ... fields lainnya
        ]
    );
}
```

### 4. View Updates

#### resources/views/pages/keuangan/oa/index.blade.php
**Kolom DataTables:**
- No. PO
- Tanggal
- CV
- No. Polisi
- **Penerima** (list semua penerima di kendaraan)
- Supplier
- Total KG
- Total OA
- **DP** (nominal DP)
- Sudah Bayar
- Sisa
- Status
- Aksi

#### resources/views/pages/keuangan/oa/bayar.blade.php
**Perubahan:**
- Variable `$penerima` → `$kendaraan`
- Menampilkan info kendaraan (no_polisi, sopir, supplier)
- Menampilkan DP nominal (jika ada)
- Menampilkan list semua penerima dengan detail pakan masing-masing
- Form action menggunakan `encrypt($kendaraan->id)`

## Keuntungan Pendekatan Ini

### 1. Konsistensi Data
- 1 kendaraan = 1 record pembayaran
- Tidak ada duplikasi atau fragmentasi data pembayaran
- Mudah tracking status pembayaran per kendaraan

### 2. Simplifikasi Logika
- DP dan pembayaran OA dalam 1 record
- Tidak perlu logic "penerima pertama" untuk DP
- Query lebih sederhana (by kendaraan, bukan by penerima)

### 3. User Experience
- User melihat pembayaran per kendaraan (lebih natural)
- Satu form pembayaran untuk semua penerima di kendaraan
- Status pembayaran lebih jelas

### 4. Fleksibilitas
- Mudah menambah pembayaran cicilan
- Mudah tracking history pembayaran
- Mudah generate laporan per kendaraan

## Flow Pembayaran

### Saat Create PO dengan DP:
1. User input DP di form kendaraan
2. System create kendaraan dengan data DP
3. System create semua penerima dan pakan
4. System hitung total OA dari semua penerima
5. System create 1 OaPayment dengan:
   - `jumlah_tagihan` = total OA
   - `jumlah_bayar` = DP nominal
   - `status` = 'partial' (jika DP < total) atau 'lunas' (jika DP >= total)

### Saat Bayar Sisa/Cicilan:
1. User buka halaman "Rekap OA"
2. User klik "Bayar" pada kendaraan
3. System tampilkan:
   - Info kendaraan
   - List semua penerima
   - Total tagihan OA
   - DP yang sudah dibayar
   - Sisa tagihan
4. User input jumlah bayar (default = sisa tagihan)
5. System update OaPayment:
   - `jumlah_bayar` += input user
   - `status` = 'lunas' jika sudah penuh, 'partial' jika belum

## Migration Files

1. **2026_05_10_090000_add_dp_fields_to_po_kendaraan.php**
   - Menambah kolom DP ke tabel `po_kendaraan`

2. **2026_05_10_100000_add_tipe_pembayaran_to_oa_payments.php**
   - Menambah `tipe_pembayaran` enum
   - Menambah `po_kendaraan_id` foreign key

## Testing Checklist

- [ ] Create PO tanpa DP → OaPayment status 'pending'
- [ ] Create PO dengan DP < total OA → OaPayment status 'partial'
- [ ] Create PO dengan DP >= total OA → OaPayment status 'lunas'
- [ ] Bayar cicilan → jumlah_bayar bertambah, status update
- [ ] Bayar lunas → status jadi 'lunas'
- [ ] Filter di Rekap OA (supplier, status, tanggal) berfungsi
- [ ] View detail pembayaran menampilkan semua penerima
- [ ] DataTables menampilkan kolom dengan benar

## Files Modified

### Controllers
- `app/Http/Controllers/PurchaseOrderController.php`
- `app/Http/Controllers/RekapOaController.php`

### Models
- `app/Models/PoKendaraan.php`
- `app/Models/OaPayment.php`

### Views
- `resources/views/pages/keuangan/oa/index.blade.php`
- `resources/views/pages/keuangan/oa/bayar.blade.php`

### Migrations
- `database/migrations/2026_05_10_090000_add_dp_fields_to_po_kendaraan.php`
- `database/migrations/2026_05_10_100000_add_tipe_pembayaran_to_oa_payments.php`

## Notes

- **PENTING**: Sebelum deploy, jalankan migration: `php artisan migrate`
- **PENTING**: Jika ada data lama (OaPayment per penerima), perlu migration data
- Field `po_penerima_id` di `oa_payments` tetap ada untuk backward compatibility, tapi untuk record baru akan NULL
- DP disimpan di `po_kendaraan`, bukan di `oa_payments` terpisah
- Total tagihan dihitung dari `ongkos_oa` (bukan `harga_pt_sum`)

## Tanggal Implementasi
10 Mei 2026
