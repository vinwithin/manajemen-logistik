# Update Tim Bongkar - Manual Input Jumlah KG

## 🎯 Problem

Tim bongkar tidak bisa tahu berapa kg yang mereka angkut per penerima secara otomatis, karena:
- 1 mobil bisa memuat untuk beberapa penerima
- 1 penerima bisa memiliki lebih dari 1 tim bongkar
- Tim bongkar perlu input manual berapa kg yang mereka bongkar

## ✅ Solution

Tambahkan field `jumlah_kg` di tabel `gudang_lansir_tim` untuk input manual.

---

## 📦 Changes Made

### 1. Database Migration
**File**: `database/migrations/2026_04_21_020000_add_jumlah_kg_to_gudang_lansir_tim.php`

Added column:
- `jumlah_kg` (decimal 12,2) - Jumlah kg yang dibongkar tim ini

**Status**: ✅ Migration run successfully

### 2. Model Update
**File**: `app/Models/GudangLansirTim.php`

Changes:
- Added `jumlah_kg` to fillable
- Added `jumlah_kg` to casts
- Added computed property `getTotalUpahAttribute()`:
  ```php
  return (float) ($this->jumlah_kg ?? 0) * ($this->upah_per_kg ?? 0);
  ```

### 3. Service Layer
**File**: `app/Services/GudangStokService.php`

Updated `prosesLansirGudangNested()`:
- Save `jumlah_kg` when creating tim record

### 4. Controller Validation
**File**: `app/Http/Controllers/GudangLansirController.php`

Added validation:
```php
'penerimas.*.tims.*.jumlah_kg' => 'required|numeric|min:0.01',
```

### 5. JavaScript Form
**File**: `public/js/gudang-lansir-create.js`

Updated `tambahTim()` function:
- Added input field for `jumlah_kg` (required)
- Layout: Nama Tim (4 cols) | Jumlah KG (3 cols) | Upah per KG (3 cols) | Hapus (2 cols)

### 6. View Show Page
**File**: `resources/views/pages/gudang/lansir/show.blade.php`

Changes:
- Tim bongkar now displayed in table format (not cards)
- Columns: Nama Tim | Jumlah (kg) | Upah per KG | Total Upah
- Shows subtotal per penerima
- Added "Total Upah Tim Bongkar" in grand total summary

---

## 🎨 New UI

### Create Form - Tim Bongkar Section
```
┌─────────────────────────────────────────────────────────────────┐
│ Tim Bongkar (Opsional)                    [+ Tambah Tim]        │
├─────────────────────────────────────────────────────────────────┤
│ Nama Tim *     │ Jumlah (kg) * │ Upah per KG │ [Hapus]         │
│ [Tim A____]    │ [100.00___]   │ [500____]   │ [X Hapus]       │
│ [Tim B____]    │ [150.00___]   │ [500____]   │ [X Hapus]       │
└─────────────────────────────────────────────────────────────────┘
```

### Show Page - Tim Bongkar Section
```
┌─────────────────────────────────────────────────────────────────┐
│ Tim Bongkar:                                                    │
├──────────────┬──────────────┬──────────────┬──────────────────┤
│ Nama Tim     │ Jumlah (kg)  │ Upah per KG  │ Total Upah       │
├──────────────┼──────────────┼──────────────┼──────────────────┤
│ Tim A        │      100     │ Rp 500       │ Rp 50,000        │
│ Tim B        │      150     │ Rp 500       │ Rp 75,000        │
├──────────────┴──────────────┴──────────────┼──────────────────┤
│                          Total Upah Tim:   │ Rp 125,000       │
└────────────────────────────────────────────┴──────────────────┘
```

### Grand Total Summary
```
┌─────────────────────────────────────────────────────────────────┐
│  Total Muatan    │  Total Karung  │  Total Ongkos  │  Total    │
│   Kendaraan      │                │     Angkut     │ Upah Tim  │
├──────────────────┼────────────────┼────────────────┼───────────┤
│   1,500 kg       │   30 karung    │  Rp 300,000    │ Rp 125,000│
└─────────────────────────────────────────────────────────────────┘
```

---

## 🧮 Calculation Logic

### Per Tim:
```
Total Upah Tim = jumlah_kg × upah_per_kg
```

**Example:**
- Tim A: 100 kg × Rp 500/kg = Rp 50,000
- Tim B: 150 kg × Rp 500/kg = Rp 75,000

### Per Penerima:
```
Total Upah Tim Penerima = SUM(tim.total_upah)
```

### Per Kendaraan:
```
Total Upah Tim Kendaraan = SUM(penerima.tims.total_upah)
```

---

## 📋 Business Logic

### Scenario 1: Single Tim per Penerima
```
Kendaraan (1,000 kg total)
├── Penerima A (600 kg)
│   ├── Pakan: BR1 (400 kg), BR2 (200 kg)
│   └── Tim: Tim A (600 kg × Rp 500) = Rp 300,000
└── Penerima B (400 kg)
    ├── Pakan: BR1 (400 kg)
    └── Tim: Tim B (400 kg × Rp 500) = Rp 200,000

Total Upah Tim: Rp 500,000
```

### Scenario 2: Multiple Tim per Penerima
```
Kendaraan (1,000 kg total)
└── Penerima A (1,000 kg)
    ├── Pakan: BR1 (600 kg), BR2 (400 kg)
    ├── Tim A (600 kg × Rp 500) = Rp 300,000
    └── Tim B (400 kg × Rp 500) = Rp 200,000

Total Upah Tim: Rp 500,000
```

### Scenario 3: Mixed
```
Kendaraan (1,500 kg total)
├── Penerima A (800 kg)
│   ├── Pakan: BR1 (500 kg), BR2 (300 kg)
│   ├── Tim A (500 kg × Rp 500) = Rp 250,000
│   └── Tim B (300 kg × Rp 500) = Rp 150,000
└── Penerima B (700 kg)
    ├── Pakan: BR1 (700 kg)
    └── Tim C (700 kg × Rp 500) = Rp 350,000

Total Upah Tim: Rp 750,000
```

---

## ✅ Validation Rules

### Form Validation:
- **Nama Tim**: Required, max 255 characters
- **Jumlah KG**: Required, numeric, min 0.01
- **Upah per KG**: Optional, numeric, min 0

### Business Rules:
- Tim bongkar is optional (penerima can have 0 tim)
- 1 penerima can have multiple tim
- Each tim must input their own jumlah_kg manually
- Total tim jumlah_kg can be ≠ total pakan jumlah_kg (flexibility)

---

## 🧪 Testing Checklist

### Test 1: Single Tim
- [ ] Create lansir with 1 penerima, 1 tim
- [ ] Input jumlah_kg and upah_per_kg
- [ ] Verify total_upah calculated correctly
- [ ] Verify displayed in show page

### Test 2: Multiple Tim per Penerima
- [ ] Create lansir with 1 penerima, 2+ tim
- [ ] Input different jumlah_kg for each tim
- [ ] Verify subtotal per penerima correct
- [ ] Verify grand total correct

### Test 3: Multiple Penerima with Tim
- [ ] Create lansir with 2+ penerima
- [ ] Each penerima has 1+ tim
- [ ] Verify grand total upah tim correct

### Test 4: Optional Tim
- [ ] Create lansir without any tim
- [ ] Verify form submits successfully
- [ ] Verify show page doesn't show tim section

### Test 5: Validation
- [ ] Try to submit tim without jumlah_kg
- [ ] Should show validation error
- [ ] Try to submit negative jumlah_kg
- [ ] Should show validation error

---

## 📊 Database Schema

```sql
CREATE TABLE gudang_lansir_tim (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    penerima_id BIGINT UNSIGNED NOT NULL,
    nama_tim VARCHAR(255) NOT NULL,
    jumlah_kg DECIMAL(12,2) NOT NULL COMMENT 'Jumlah kg yang dibongkar tim ini',
    upah_per_kg DECIMAL(15,2) NULL COMMENT 'Upah per kg dari jumlah yang dibongkar',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (penerima_id) REFERENCES gudang_lansir_penerima(id) ON DELETE CASCADE
);
```

---

## 🔄 Migration Status

```bash
php artisan migrate
```

**Output:**
```
INFO  Running migrations.

2026_04_21_020000_add_jumlah_kg_to_gudang_lansir_tim ........ DONE
```

**Status**: ✅ Complete

---

## 📝 Notes

### Why Manual Input?
- Tim bongkar tidak tahu secara pasti berapa kg yang mereka bongkar
- Bisa jadi 1 tim bongkar sebagian, tim lain bongkar sisanya
- Lebih fleksibel untuk berbagai skenario lapangan

### Flexibility:
- Total tim jumlah_kg **tidak harus sama** dengan total pakan jumlah_kg
- Ini memberikan fleksibilitas untuk:
  - Tim yang hanya bongkar sebagian
  - Pembagian kerja yang tidak merata
  - Situasi lapangan yang dinamis

### Future Enhancement:
- Add validation warning if total tim kg ≠ total pakan kg
- Add auto-distribute feature (optional)
- Add tim performance tracking

---

## ✅ Deployment Status

- [x] Migration created and run
- [x] Model updated
- [x] Service layer updated
- [x] Controller validation updated
- [x] JavaScript form updated
- [x] View show page updated
- [x] Caches cleared
- [ ] User testing

---

**Update Date**: April 21, 2026  
**Status**: ✅ COMPLETE  
**Ready for Testing**: YES
