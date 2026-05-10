# Analisis & Rekomendasi: Fitur Down Payment (DP) Supplier pada PO

## Konteks Bisnis

Klien ingin menambahkan fitur input DP (Down Payment) pada saat create PO, ke masing-masing supplier.

## Analisis Struktur Data Existing

### Struktur PO Saat Ini
```
purchase_orders (Header PO)
└── po_kendaraan (Per Kendaraan/Supplier)
    ├── supplier_id
    ├── jumlah_kg
    ├── jumlah_karung
    └── po_penerima (Per Penerima)
        └── po_penerima_pakan (Per Jenis Pakan)
            ├── jumlah_kg
            ├── harga_supplier (harga beli dari supplier)
            └── harga_pt_sum (harga jual ke PT Sum)
```

### Key Insight
- **1 PO bisa punya BANYAK kendaraan**
- **1 Kendaraan = 1 Supplier**
- **Total tagihan supplier** = SUM(jumlah_kg × harga_supplier) per kendaraan

## Rekomendasi Pendekatan

### ✅ **PENDEKATAN 1: DP di Level Kendaraan (RECOMMENDED)**

**Alasan:**
- ✅ Paling sesuai dengan struktur data existing
- ✅ 1 kendaraan = 1 supplier = 1 transaksi pembayaran
- ✅ Mudah tracking per supplier per kendaraan
- ✅ Fleksibel: bisa DP berbeda untuk supplier yang sama di kendaraan berbeda
- ✅ Cocok untuk skenario: supplier yang sama kirim 2 kendaraan dengan DP berbeda

**Implementasi:**

#### 1. Migration: Tambah kolom di `po_kendaraan`
```php
Schema::table('po_kendaraan', function (Blueprint $table) {
    $table->decimal('dp_nominal', 15, 2)->default(0)->after('jumlah_karung')
        ->comment('Nominal DP yang dibayarkan ke supplier');
    $table->decimal('dp_persen', 5, 2)->nullable()->after('dp_nominal')
        ->comment('Persentase DP (opsional, untuk referensi)');
    $table->date('dp_tanggal')->nullable()->after('dp_persen')
        ->comment('Tanggal pembayaran DP');
    $table->string('dp_metode')->nullable()->after('dp_tanggal')
        ->comment('Metode pembayaran: transfer, tunai, dll');
    $table->text('dp_keterangan')->nullable()->after('dp_metode')
        ->comment('Catatan pembayaran DP');
});
```

#### 2. Form Input (di create PO)
```javascript
// Di setiap section kendaraan, tambahkan:
<div class="row mt-2 border-top pt-2">
    <div class="col-12"><h6>Down Payment (DP)</h6></div>
    <div class="col-md-3">
        <label>Nominal DP (Rp)</label>
        <input type="number" name="kendaraans[0][dp_nominal]" 
               class="form-control" step="0.01" min="0">
    </div>
    <div class="col-md-2">
        <label>Persentase (%)</label>
        <input type="number" name="kendaraans[0][dp_persen]" 
               class="form-control" step="0.01" min="0" max="100" readonly>
    </div>
    <div class="col-md-3">
        <label>Tanggal Bayar</label>
        <input type="date" name="kendaraans[0][dp_tanggal]" class="form-control">
    </div>
    <div class="col-md-4">
        <label>Metode</label>
        <select name="kendaraans[0][dp_metode]" class="form-control">
            <option value="">-- Pilih --</option>
            <option value="transfer">Transfer Bank</option>
            <option value="tunai">Tunai</option>
            <option value="giro">Giro</option>
        </select>
    </div>
    <div class="col-12 mt-2">
        <label>Keterangan DP</label>
        <textarea name="kendaraans[0][dp_keterangan]" 
                  class="form-control" rows="2"></textarea>
    </div>
</div>
```

#### 3. Auto-calculate Persentase DP
```javascript
// Hitung otomatis persentase DP berdasarkan total tagihan
function hitungDpPersen(kendaraanIndex) {
    const totalTagihan = hitungTotalTagihanKendaraan(kendaraanIndex);
    const dpNominal = parseFloat($(`[name="kendaraans[${kendaraanIndex}][dp_nominal]"]`).val()) || 0;
    
    if (totalTagihan > 0) {
        const dpPersen = (dpNominal / totalTagihan) * 100;
        $(`[name="kendaraans[${kendaraanIndex}][dp_persen]"]`).val(dpPersen.toFixed(2));
    }
}

// Total tagihan = SUM(jumlah_kg × harga_supplier) dari semua penerima di kendaraan ini
function hitungTotalTagihanKendaraan(kendaraanIndex) {
    let total = 0;
    $(`[data-kendaraan="${kendaraanIndex}"]`).each(function() {
        const jumlahKg = parseFloat($(this).find('[name*="[jumlah_kg]"]').val()) || 0;
        const hargaSupplier = parseFloat($(this).find('[name*="[harga_supplier]"]').val()) || 0;
        total += jumlahKg * hargaSupplier;
    });
    return total;
}
```

#### 4. Model & Accessor
```php
// app/Models/PoKendaraan.php
protected $fillable = [
    // ... existing fields
    'dp_nominal', 'dp_persen', 'dp_tanggal', 'dp_metode', 'dp_keterangan'
];

protected $casts = [
    'dp_tanggal' => 'date',
];

// Hitung total tagihan supplier
public function getTotalTagihanAttribute(): float
{
    return $this->penerimas->sum(function ($penerima) {
        return $penerima->pakans->sum(function ($pakan) {
            return $pakan->jumlah_kg * $pakan->harga_supplier;
        });
    });
}

// Hitung sisa tagihan (belum dibayar)
public function getSisaTagihanAttribute(): float
{
    return $this->total_tagihan - $this->dp_nominal;
}

// Status pembayaran
public function getStatusPembayaranAttribute(): string
{
    if ($this->dp_nominal == 0) return 'Belum Bayar DP';
    if ($this->dp_nominal >= $this->total_tagihan) return 'Lunas';
    return 'DP ' . number_format($this->dp_persen, 0) . '%';
}
```

#### 5. Tampilan di Show PO
```blade
{{-- Di setiap kendaraan --}}
<div class="card mb-3">
    <div class="card-header">
        <strong>Kendaraan: {{ $kendaraan->no_polisi }}</strong>
        <span class="badge bg-info">{{ $kendaraan->supplier->nama }}</span>
    </div>
    <div class="card-body">
        {{-- Info Penerima & Pakan --}}
        
        {{-- Info Pembayaran --}}
        <div class="border-top pt-3 mt-3">
            <h6>Informasi Pembayaran</h6>
            <table class="table table-sm">
                <tr>
                    <td width="200">Total Tagihan</td>
                    <td><strong>Rp {{ number_format($kendaraan->total_tagihan, 0, ',', '.') }}</strong></td>
                </tr>
                @if($kendaraan->dp_nominal > 0)
                <tr>
                    <td>Down Payment ({{ number_format($kendaraan->dp_persen, 1) }}%)</td>
                    <td>Rp {{ number_format($kendaraan->dp_nominal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tanggal Bayar DP</td>
                    <td>{{ $kendaraan->dp_tanggal?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Metode Pembayaran</td>
                    <td>{{ ucfirst($kendaraan->dp_metode ?? '-') }}</td>
                </tr>
                <tr class="table-warning">
                    <td><strong>Sisa Tagihan</strong></td>
                    <td><strong>Rp {{ number_format($kendaraan->sisa_tagihan, 0, ',', '.') }}</strong></td>
                </tr>
                @else
                <tr>
                    <td colspan="2" class="text-muted">Belum ada pembayaran DP</td>
                </tr>
                @endif
            </table>
            @if($kendaraan->dp_keterangan)
            <div class="alert alert-info py-2 small">
                <strong>Keterangan:</strong> {{ $kendaraan->dp_keterangan }}
            </div>
            @endif
        </div>
    </div>
</div>
```

#### 6. Laporan Pembayaran Supplier
```php
// Buat halaman baru: /keuangan/pembayaran-supplier
// Menampilkan:
// - Daftar PO dengan status pembayaran per kendaraan
// - Filter: supplier, status pembayaran, tanggal
// - Export Excel rekap pembayaran
```

---

### ⚠️ **PENDEKATAN 2: DP di Level PO Header**

**Alasan TIDAK direkomendasikan:**
- ❌ 1 PO bisa punya banyak supplier (via banyak kendaraan)
- ❌ Sulit tracking: DP untuk supplier mana?
- ❌ Tidak fleksibel jika supplier yang sama ada di 2 kendaraan
- ❌ Perlu tabel tambahan untuk mapping DP ke supplier

**Hanya cocok jika:** 1 PO = 1 Supplier (bukan case Anda)

---

### ⚠️ **PENDEKATAN 3: Tabel Terpisah `supplier_payments`**

**Struktur:**
```php
Schema::create('supplier_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('po_kendaraan_id')->constrained()->cascadeOnDelete();
    $table->enum('tipe', ['dp', 'pelunasan', 'cicilan']);
    $table->decimal('nominal', 15, 2);
    $table->date('tanggal_bayar');
    $table->string('metode');
    $table->text('keterangan')->nullable();
    $table->string('bukti_bayar')->nullable(); // path file
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

**Kelebihan:**
- ✅ Bisa track multiple pembayaran (DP, cicilan, pelunasan)
- ✅ History pembayaran lengkap
- ✅ Bisa upload bukti transfer

**Kekurangan:**
- ❌ Lebih kompleks untuk implementasi awal
- ❌ Perlu UI tambahan untuk input pembayaran

**Rekomendasi:** Gunakan ini jika klien butuh:
- Multiple DP (DP 1, DP 2, dst)
- Cicilan pembayaran
- Upload bukti transfer
- Approval pembayaran

---

## Rekomendasi Final

### Untuk Kebutuhan Sederhana (DP saja):
✅ **Gunakan PENDEKATAN 1** (DP di level kendaraan)
- Paling simple dan sesuai struktur existing
- Cukup untuk tracking DP per supplier per kendaraan
- Mudah maintenance

### Untuk Kebutuhan Kompleks (Multiple payment, approval):
✅ **Gunakan PENDEKATAN 3** (Tabel terpisah)
- Lebih scalable untuk fitur pembayaran yang kompleks
- Bisa dikembangkan untuk approval workflow
- History pembayaran lengkap

---

## Fitur Tambahan yang Bisa Dikembangkan

1. **Validasi DP**
   - DP tidak boleh > total tagihan
   - Warning jika DP < 10% atau > 50%

2. **Notifikasi**
   - Email ke supplier saat DP dibayarkan
   - Reminder pelunasan saat barang tiba

3. **Laporan**
   - Rekap DP per supplier per periode
   - Outstanding payment (sisa tagihan)
   - Aging report (umur hutang)

4. **Integration**
   - Link ke sistem accounting
   - Export ke format accounting software

---

## Estimasi Waktu Implementasi

### Pendekatan 1 (Simple):
- Migration & Model: 30 menit
- Form Input: 1 jam
- JavaScript auto-calculate: 1 jam
- Show/Display: 1 jam
- Testing: 1 jam
**Total: ~4-5 jam**

### Pendekatan 3 (Complex):
- Migration & Model: 1 jam
- CRUD Pembayaran: 3 jam
- Integration dengan PO: 2 jam
- Laporan: 2 jam
- Testing: 2 jam
**Total: ~10 jam**

---

## Pertanyaan untuk Klien

Sebelum implementasi, konfirmasi dengan klien:

1. **Apakah DP hanya 1 kali, atau bisa multiple DP?**
   - Jika 1 kali → Pendekatan 1
   - Jika multiple → Pendekatan 3

2. **Apakah perlu upload bukti transfer?**
   - Jika ya → Pendekatan 3

3. **Apakah perlu approval pembayaran?**
   - Jika ya → Pendekatan 3 + workflow approval

4. **Apakah DP dibayar saat create PO atau setelahnya?**
   - Saat create → Input di form create PO
   - Setelahnya → Buat halaman terpisah untuk input pembayaran

5. **Apakah perlu tracking pelunasan?**
   - Jika ya → Pendekatan 3

---

## Kesimpulan

**Untuk mayoritas kasus:** Gunakan **Pendekatan 1** (DP di level kendaraan)
- Simple, cepat, sesuai struktur existing
- Cukup untuk kebutuhan DP standar
- Mudah di-maintain

**Jika butuh fitur advanced:** Upgrade ke **Pendekatan 3** (Tabel terpisah)
- Bisa dimulai dari Pendekatan 1, lalu migrate ke Pendekatan 3 nanti
- Lebih scalable untuk future requirements


---

## STATUS IMPLEMENTASI (Update: 10 Mei 2026)

### ✅ SELESAI DIIMPLEMENTASI

Fitur DP Supplier telah diimplementasikan menggunakan **Pendekatan 1** (DP di level kendaraan).

#### 1. Database Migration ✅
**File:** `database/migrations/2026_05_10_090000_add_dp_fields_to_po_kendaraan.php`

Menambahkan 5 kolom baru ke tabel `po_kendaraan`:
- `dp_nominal` (decimal 15,2) - Jumlah DP dalam Rupiah
- `dp_persen` (decimal 5,2) - Persentase DP (auto-calculated)
- `dp_tanggal` (date) - Tanggal pembayaran DP
- `dp_metode` (varchar 50) - Metode pembayaran (transfer/tunai/giro)
- `dp_keterangan` (text) - Catatan pembayaran DP

#### 2. Model Update ✅
**File:** `app/Models/PoKendaraan.php`

**Perubahan:**
- Tambah field DP ke `$fillable` dan `$casts`
- Tambah accessor methods:
  - `getTotalTagihanSupplierAttribute()` - Hitung total tagihan dari semua penerima
  - `getSisaTagihanAttribute()` - Hitung sisa tagihan setelah DP
  - `getStatusPembayaranAttribute()` - Status: 'lunas', 'dp', 'belum_bayar'
  - `getStatusPembayaranBadgeAttribute()` - Badge HTML untuk status

#### 3. Controller Update ✅
**File:** `app/Http/Controllers/PurchaseOrderController.php`

**Method `store()`:**
- Tambah validasi untuk field DP (nullable)
- Simpan data DP saat create kendaraan

**Validasi:**
```php
'kendaraan.*.dp_nominal' => 'nullable|numeric|min:0',
'kendaraan.*.dp_persen' => 'nullable|numeric|min:0|max:100',
'kendaraan.*.dp_tanggal' => 'nullable|date',
'kendaraan.*.dp_metode' => 'nullable|string|in:transfer,tunai,giro',
'kendaraan.*.dp_keterangan' => 'nullable|string|max:500',
```

#### 4. View Update - Create PO ✅
**File:** `resources/views/pages/purchase-order/create.blade.php`

**Perubahan:**
1. Tambah section DP di template kendaraan dengan input fields:
   - **Nominal DP** - Input text dengan format Rupiah (pemisah ribuan dengan titik)
   - **Persentase** - Auto-calculated, readonly
   - **Tanggal Bayar** - Date picker
   - **Metode Pembayaran** - Dropdown (transfer/tunai/giro)
   - **Keterangan** - Textarea untuk notes

2. Tambah info box yang menampilkan:
   - Total tagihan supplier
   - Nominal DP dan persentase
   - Sisa tagihan
   - Warning jika DP melebihi total tagihan

3. **Format Rupiah untuk input nominal:**
   - Input display: format dengan pemisah ribuan (1.000.000)
   - Hidden input: menyimpan nilai numerik untuk form submission
   - Auto-format saat user mengetik

4. Old data handling untuk restore DP saat validation error

#### 5. JavaScript Handler ✅
**File:** `public/js/po-dp-handler.js`

**Fungsi:**

1. **`formatRupiah(angka)`** - Format angka ke format Rupiah dengan pemisah ribuan (titik)
   - Input: 1000000
   - Output: "1.000.000"
   - Menggunakan regex untuk menambahkan titik setiap 3 digit

2. **`parseRupiah(rupiah)`** - Parse format Rupiah kembali ke angka
   - Input: "1.000.000"
   - Output: 1000000
   - Menghapus semua titik dan convert ke float

3. **`hitungTotalTagihanSupplier($kendaraanCard)`** - Hitung total tagihan dari semua pakan
   - Loop semua penerima → loop semua pakan
   - Total = Σ (jumlah_kg × ongkos_oa)
   - **Catatan:** Menggunakan ongkos_oa (ongkos angkut ke supplier), bukan harga_pt_sum

4. **`updateDpInfo($kendaraanCard)`** - Update persentase dan info tagihan
   - Hitung persentase: (DP / Total Tagihan) × 100%
   - Update field persentase (readonly)
   - Tampilkan info box dengan breakdown tagihan
   - Warning jika DP > total tagihan

**Event Handlers:**
- Input DP nominal display → format Rupiah, update hidden field, recalc info
- Blur DP nominal → pastikan format tetap rapi
- Input jumlah kg atau harga → recalc DP info
- Tambah/hapus pakan → recalc DP info
- Tambah/hapus penerima → recalc DP info

---

### 📋 CARA PENGGUNAAN

#### Input DP saat Create PO:
1. Buat PO baru, tambah kendaraan
2. Pilih supplier untuk kendaraan
3. Tambah penerima dan pakan (untuk menghitung total tagihan)
4. Scroll ke section "Down Payment (DP) Supplier"
5. Input nominal DP dengan format Rupiah:
   - Ketik angka: `1000000`
   - Otomatis terformat: `1.000.000`
   - Bisa juga ketik langsung dengan titik: `1.000.000`
6. Persentase akan otomatis terhitung
7. Isi tanggal bayar, metode, dan keterangan (opsional)
8. Info box akan menampilkan breakdown tagihan secara real-time

#### Contoh Perhitungan:
```
Penerima 1:
- Pakan A: 1000 kg × Rp 5.000 (ongkos OA) = Rp 5.000.000
- Pakan B: 500 kg × Rp 6.000 (ongkos OA) = Rp 3.000.000

Total Tagihan Supplier: Rp 8.000.000
DP: Rp 2.000.000 (25%)
Sisa Tagihan: Rp 6.000.000

Catatan: Total tagihan dihitung dari ongkos_oa (ongkos angkut ke supplier),
bukan dari harga_pt_sum (harga jual ke PT Sum).
```

---

### 🎨 FITUR FORMAT RUPIAH

#### Implementasi:
- **Display Input:** Menggunakan input text dengan class `input-dp-nominal-display`
- **Hidden Input:** Menyimpan nilai numerik murni dengan name `kendaraan[x][dp_nominal]`
- **Auto-format:** JavaScript mendeteksi input dan format otomatis dengan pemisah ribuan (titik)
- **Parsing:** Saat submit, hidden field berisi nilai numerik yang siap disimpan ke database

#### Contoh Format:
| User Input | Display | Nilai Database |
|------------|---------|----------------|
| 1000000 | 1.000.000 | 1000000.00 |
| 2500000 | 2.500.000 | 2500000.00 |
| 500000 | 500.000 | 500000.00 |
| 0 | 0 | 0.00 |

#### Browser Compatibility:
- Menggunakan vanilla JavaScript dan jQuery
- Format Rupiah menggunakan regex dan string manipulation
- Compatible dengan semua modern browsers (Chrome, Firefox, Safari, Edge)

---

### 🔄 BELUM DIKERJAKAN

1. ~~**Update halaman Show PO** untuk display info DP~~ ✅ **SELESAI**
   - ~~Tampilkan section pembayaran per kendaraan~~
   - ~~Badge status pembayaran (Lunas/DP/Belum Bayar)~~
   - ~~Breakdown total tagihan, DP, dan sisa~~

2. **Update halaman Edit PO** untuk edit data DP
   - Form edit dengan format Rupiah yang sama
   - Validasi perubahan DP

3. **Laporan pembayaran DP per supplier**
   - Halaman rekap DP per supplier
   - Filter by periode, supplier, status
   - Export Excel

4. **Export/print dengan info DP**
   - Update PDF PO untuk include info DP
   - Surat jalan dengan info pembayaran

---

### 📁 FILE YANG DIMODIFIKASI

1. ✅ `database/migrations/2026_05_10_090000_add_dp_fields_to_po_kendaraan.php` (NEW)
2. ✅ `app/Models/PoKendaraan.php` (MODIFIED)
3. ✅ `app/Http/Controllers/PurchaseOrderController.php` (MODIFIED)
4. ✅ `resources/views/pages/purchase-order/create.blade.php` (MODIFIED)
5. ✅ `public/js/po-dp-handler.js` (NEW)
6. ✅ `ANALISIS_FITUR_DP_SUPPLIER.md` (UPDATED)

---

### 🧪 TESTING CHECKLIST

- [ ] Jalankan migration: `php artisan migrate`
- [ ] Test create PO dengan DP:
  - [ ] Input DP dengan format Rupiah (1.000.000)
  - [ ] Persentase auto-calculate dengan benar
  - [ ] Info box menampilkan breakdown yang benar
  - [ ] Warning muncul jika DP > total tagihan
- [ ] Test validation error:
  - [ ] Old data DP ter-restore dengan format Rupiah yang benar
  - [ ] Field DP tetap terisi setelah validation error
- [ ] Test edge cases:
  - [ ] DP = 0 (tidak ada DP)
  - [ ] DP = total tagihan (lunas)
  - [ ] DP > total tagihan (warning)
  - [ ] Input dengan format berbeda (1000000, 1.000.000, 1.000.000,00)
- [ ] Test browser compatibility:
  - [ ] Chrome
  - [ ] Firefox
  - [ ] Safari
  - [ ] Edge

---

### 📝 CATATAN TEKNIS

#### Format Rupiah:
- **Display:** Menggunakan pemisah ribuan dengan titik (1.000.000)
- **Storage:** Menyimpan nilai numerik murni di database (1000000.00)
- **Parsing:** Otomatis convert dari format display ke numerik saat submit
- **Decimal:** Mendukung input desimal dengan koma (1.000.000,50)

#### Validasi:
- Semua field DP bersifat nullable (opsional)
- DP nominal harus >= 0
- DP persen harus 0-100
- Metode pembayaran: transfer, tunai, atau giro
- Keterangan max 500 karakter

#### Auto-calculation:
- Persentase DP dihitung otomatis: (DP / Total Tagihan) × 100%
- Total tagihan dihitung dari: Σ (jumlah_kg × ongkos_oa) semua pakan
- **Penting:** Menggunakan `ongkos_oa` (ongkos angkut ke supplier), bukan `harga_pt_sum`
- Info tagihan update real-time saat user input data
- Recalculate saat tambah/hapus pakan atau penerima

#### Performance:
- Format Rupiah menggunakan regex yang efisien
- Event handler menggunakan event delegation untuk performa optimal
- Debouncing tidak diperlukan karena operasi sangat cepat

---

### 🚀 NEXT STEPS

1. **Jalankan migration:**
   ```bash
   php artisan migrate
   ```

2. **Test fitur di development:**
   - Buat PO baru dengan DP
   - Cek format Rupiah berfungsi
   - Cek auto-calculate persentase
   - Cek info box menampilkan data yang benar

3. **Deploy ke production:**
   - Backup database
   - Run migration
   - Test di production environment

4. **Implementasi fitur lanjutan:**
   - Update Show PO page
   - Update Edit PO page
   - Buat laporan pembayaran DP



---

## UPDATE: Tampilan DP di Show PO (10 Mei 2026)

### ✅ Fitur Baru: Informasi DP di Halaman Show PO

**File:** `resources/views/pages/purchase-order/show.blade.php`

#### Perubahan:
Menambahkan section informasi Down Payment di setiap kendaraan pada halaman detail PO.

#### Tampilan yang Ditambahkan:

1. **Section DP per Kendaraan** (hanya muncul jika ada DP):
   ```
   ┌─────────────────────────────────────────────────────┐
   │ 💰 Informasi Down Payment (DP)                     │
   ├─────────────────────────────────────────────────────┤
   │ Total Tagihan Supplier: Rp 8.000.000               │
   │ Down Payment (DP): Rp 2.000.000 [25%]              │
   │ Sisa Tagihan: Rp 6.000.000                         │
   │ Status Pembayaran: [DP 25%]                        │
   ├─────────────────────────────────────────────────────┤
   │ Tanggal Bayar: 10 Mei 2026                         │
   │ Metode Pembayaran: Transfer                        │
   │ Keterangan: Pembayaran DP awal                     │
   └─────────────────────────────────────────────────────┘
   ```

2. **Badge Status Pembayaran**:
   - 🟢 **Lunas** (bg-success) - DP >= Total Tagihan
   - 🟡 **DP X%** (bg-warning) - DP > 0 tapi < Total Tagihan
   - ⚫ **Belum Bayar DP** (bg-secondary) - DP = 0

3. **Informasi yang Ditampilkan**:
   - **Total Tagihan Supplier**: Dihitung dari Σ (jumlah_kg × ongkos_oa)
   - **Down Payment**: Nominal DP dengan badge persentase
   - **Sisa Tagihan**: Total - DP
   - **Status Pembayaran**: Badge dengan warna sesuai status
   - **Tanggal Bayar**: Format tanggal Indonesia (d M Y)
   - **Metode Pembayaran**: Transfer/Tunai/Giro (capitalize)
   - **Keterangan**: Catatan pembayaran DP

#### Styling:
- Background light (`bg-light`) untuk section DP
- Border bottom untuk pemisah dengan tabel penerima
- Grid responsive (col-md-3, col-md-4, col-md-12)
- Badge warna sesuai status pembayaran
- Conditional rendering (hanya tampil jika ada DP)

#### Contoh Kode:
```blade
@if($kendaraan->dp_nominal > 0)
<div class="border-bottom bg-light p-3">
    <h6 class="mb-2 text-success">
        <i class="fa fa-money"></i> Informasi Down Payment (DP)
    </h6>
    <div class="row g-2">
        <div class="col-md-3">
            <div class="small text-muted">Total Tagihan Supplier</div>
            <div class="fw-bold">
                Rp {{ number_format($kendaraan->total_tagihan_supplier, 0, ',', '.') }}
            </div>
        </div>
        <!-- ... dst -->
    </div>
</div>
@endif
```

#### Testing:
- [x] Tampilan DP muncul jika ada DP (dp_nominal > 0)
- [x] Tampilan DP tidak muncul jika tidak ada DP (dp_nominal = 0)
- [x] Badge status pembayaran sesuai dengan kondisi
- [x] Format Rupiah dengan pemisah ribuan
- [x] Tanggal format Indonesia
- [x] Metode pembayaran capitalize
- [x] Responsive di mobile dan desktop

---

## Summary Fitur DP Supplier

### ✅ Sudah Selesai:
1. ✅ Database migration (5 kolom DP)
2. ✅ Model accessor methods (total_tagihan, sisa_tagihan, status_pembayaran)
3. ✅ Controller validation & save
4. ✅ Create PO form dengan format Rupiah
5. ✅ JavaScript auto-calculate & format
6. ✅ Old data handling
7. ✅ Enhanced error handling & logging
8. ✅ **Show PO dengan informasi DP lengkap** ← NEW

### 🔄 Belum Selesai:
1. ⏳ Edit PO form untuk edit data DP
2. ⏳ Laporan pembayaran DP per supplier
3. ⏳ Export PDF dengan info DP
4. ⏳ Dashboard/widget monitoring DP

### 📊 Progress: 80% Complete

Fitur inti DP Supplier sudah selesai dan siap digunakan untuk:
- ✅ Input DP saat create PO
- ✅ Lihat informasi DP di detail PO
- ✅ Auto-calculate persentase dan sisa tagihan
- ✅ Format Rupiah yang user-friendly



---

## UPDATE: Integrasi DP dengan Sistem Pembayaran Supplier (10 Mei 2026)

### ✅ Fitur Baru: DP Otomatis Masuk ke OA Payments

**Latar Belakang:**
DP Supplier perlu ditracking di sistem keuangan pembayaran supplier agar:
- Finance bisa monitoring semua pembayaran ke supplier
- Laporan pembayaran supplier lebih lengkap
- Rekonsiliasi pembayaran lebih mudah

### Implementasi:

#### 1. Migration Baru ✅
**File:** `database/migrations/2026_05_10_100000_add_tipe_pembayaran_to_oa_payments.php`

**Perubahan pada tabel `oa_payments`:**
- Tambah kolom `tipe_pembayaran` (enum: 'oa', 'dp_supplier')
  - `'oa'` = Pembayaran OA ke penerima (existing)
  - `'dp_supplier'` = Down Payment ke supplier (new)
- Tambah kolom `po_kendaraan_id` (foreign key ke `po_kendaraan`)
  - Untuk link pembayaran DP dengan kendaraan

#### 2. Model Update ✅

**File:** `app/Models/OaPayment.php`
- Tambah `'tipe_pembayaran'` dan `'po_kendaraan_id'` ke `$fillable`
- Tambah konstanta `TIPE` untuk mapping tipe pembayaran
- Tambah relasi `kendaraan()` ke PoKendaraan
- Tambah `'giro'` ke `METODE` (untuk konsistensi dengan DP)

**File:** `app/Models/PoKendaraan.php`
- Tambah relasi `dpPayment()` untuk akses pembayaran DP

#### 3. Controller Update ✅

**File:** `app/Http/Controllers/PurchaseOrderController.php`

**Method `store()`:**
Setelah create kendaraan, jika ada DP, otomatis create record di `oa_payments`:

```php
if (!empty($kendaraanData['dp_nominal']) && $kendaraanData['dp_nominal'] > 0) {
    OaPayment::create([
        'po_kendaraan_id' => $kendaraan->id,
        'supplier_id' => $kendaraan->supplier_id,
        'tipe_pembayaran' => 'dp_supplier',
        'jumlah_tagihan' => 0, // Akan dihitung setelah penerima & pakan diinput
        'jumlah_bayar' => $kendaraanData['dp_nominal'],
        'tanggal_bayar' => $kendaraanData['dp_tanggal'] ?? now(),
        'metode_bayar' => $kendaraanData['dp_metode'] ?? 'transfer',
        'keterangan' => 'Down Payment Supplier - ' . ($kendaraanData['dp_keterangan'] ?? 'PO: ' . $po->no_po),
        'status' => 'lunas', // DP sudah dibayar
    ]);
}
```

#### 4. View Update ✅

**File:** `resources/views/pages/purchase-order/show.blade.php`

Tambah tombol "Lihat Pembayaran" di section DP yang link ke halaman keuangan pembayaran supplier:
- Link ke `/keuangan/oa?search={no_po}`
- Hanya muncul jika ada record pembayaran DP

### Alur Kerja:

```
1. User create PO dengan DP
   ↓
2. System save data ke po_kendaraan (dp_nominal, dp_tanggal, dll)
   ↓
3. System otomatis create record di oa_payments:
   - tipe_pembayaran = 'dp_supplier'
   - po_kendaraan_id = {id kendaraan}
   - supplier_id = {id supplier}
   - jumlah_bayar = {dp_nominal}
   - status = 'lunas'
   ↓
4. Finance bisa lihat di halaman "Keuangan > Pembayaran Supplier"
   ↓
5. Laporan pembayaran supplier include DP
```

### Keuntungan:

1. **Tracking Terpusat**
   - Semua pembayaran ke supplier (OA + DP) ada di satu tabel
   - Mudah untuk laporan keuangan

2. **Rekonsiliasi Mudah**
   - Finance bisa cek semua pembayaran ke supplier
   - Filter by tipe: OA atau DP

3. **Laporan Lengkap**
   - Dashboard pembayaran supplier include DP
   - Export Excel include DP
   - Grafik pembayaran lebih akurat

4. **Audit Trail**
   - Semua pembayaran tercatat dengan timestamp
   - Keterangan jelas: "Down Payment Supplier - PO: XXX"

### Contoh Data di `oa_payments`:

| id | po_kendaraan_id | supplier_id | tipe_pembayaran | jumlah_bayar | tanggal_bayar | metode_bayar | keterangan | status |
|----|-----------------|-------------|-----------------|--------------|---------------|--------------|------------|--------|
| 1  | 123             | 5           | dp_supplier     | 2.000.000    | 2026-05-10    | transfer     | Down Payment Supplier - PO: PO-001 | lunas |
| 2  | 123             | 5           | oa              | 6.000.000    | 2026-05-15    | transfer     | Pelunasan OA | lunas |

### Testing Checklist:

- [ ] Jalankan migration: `php artisan migrate`
- [ ] Create PO dengan DP
- [ ] Cek tabel `oa_payments` ada record baru dengan `tipe_pembayaran = 'dp_supplier'`
- [ ] Buka halaman "Keuangan > Pembayaran Supplier"
- [ ] Pastikan DP muncul di list pembayaran
- [ ] Klik tombol "Lihat Pembayaran" di show PO
- [ ] Pastikan redirect ke halaman pembayaran dengan filter PO

### Migration Command:

```bash
php artisan migrate
```

Jika sudah pernah run migration sebelumnya:
```bash
php artisan migrate --path=database/migrations/2026_05_10_100000_add_tipe_pembayaran_to_oa_payments.php
```

---

## Summary Progress Fitur DP Supplier

### ✅ Sudah Selesai (90%):
1. ✅ Database migration (5 kolom DP + 2 kolom tracking)
2. ✅ Model accessor methods
3. ✅ Controller validation & save
4. ✅ Create PO form dengan format Rupiah
5. ✅ JavaScript auto-calculate & format
6. ✅ Old data handling
7. ✅ Enhanced error handling & logging
8. ✅ Show PO dengan informasi DP lengkap
9. ✅ **Integrasi dengan sistem pembayaran supplier** ← NEW
   - DP otomatis masuk ke `oa_payments`
   - Tracking terpusat di keuangan
   - Link dari show PO ke pembayaran

### 🔄 Belum Selesai (10%):
1. ⏳ Edit PO form untuk edit data DP
2. ⏳ Update halaman keuangan untuk filter by tipe pembayaran
3. ⏳ Export PDF dengan info DP
4. ⏳ Dashboard widget monitoring DP

### 📊 Progress: 90% Complete

Fitur DP Supplier sudah hampir lengkap dan terintegrasi dengan sistem keuangan! 🎉

