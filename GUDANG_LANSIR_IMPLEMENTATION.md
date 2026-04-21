# Implementasi Gudang Lansir Nested

## Status: ✅ Database & Models Selesai | 🔄 Controller & View Dalam Progress

### 1. Database Structure (✅ DONE)

**Migration:** `2026_04_21_010000_restructure_gudang_lansir_to_nested.php`

```
gudang_lansir_kendaraan (Level 1)
├── gudang_lansir_penerima (Level 2a)
│   ├── gudang_lansir_pakan (Level 3)
│   └── gudang_lansir_tim (Level 2b)
```

### 2. Models Created (✅ DONE)

- ✅ `GudangLansirKendaraan.php`
- ✅ `GudangLansirPenerima.php`
- ✅ `GudangLansirPakan.php`
- ✅ `GudangLansirTim.php` (updated)

### 3. Service Method (✅ DONE)

✅ `GudangStokService::prosesLansirGudangNested()`
- Validasi stok tersedia
- Kurangi stok per pakan
- Catat mutasi keluar
- Return kendaraan object

### 4. Controller Methods (🔄 TODO)

File: `app/Http/Controllers/GudangLansirController.php`

```php
public function create(Request $request)
{
    $gudangId = $request->gudang_id ?? session('active_gudang');
    $gudangs = Tujuan::where('type', 'gudang')->where('is_aktif', true)->get();
    $tujuans = Tujuan::where('is_aktif', true)->orderBy('nama')->get();
    $kodePakans = KodePakan::orderBy('kode')->get();
    
    // Get stok tersedia per gudang
    $stokList = [];
    if ($gudangId) {
        $stokList = GudangStok::where('tujuan_id', $gudangId)
            ->with('kodePakan')
            ->where('stok_kg', '>', 0)
            ->get();
    }
    
    return view('pages.gudang.lansir.create', compact('gudangs', 'tujuans', 'kodePakans', 'gudangId', 'stokList'));
}

public function store(Request $request)
{
    $request->validate([
        'gudang_id' => 'required|exists:tujuan,id',
        'no_polisi' => 'required|string|max:20',
        'nama_sopir' => 'nullable|string|max:255',
        'no_surat_jalan' => 'nullable|string|max:100',
        'tanggal_lansir' => 'required|date',
        'catatan' => 'nullable|string',
        'penerimas' => 'required|array|min:1',
        'penerimas.*.nama_penerima' => 'required|string|max:255',
        'penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
        'penerimas.*.pakans' => 'required|array|min:1',
        'penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
        'penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
        'penerimas.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
        'penerimas.*.tims' => 'nullable|array',
        'penerimas.*.tims.*.nama_tim' => 'required|string|max:255',
        'penerimas.*.tims.*.upah_per_kg' => 'nullable|numeric|min:0',
    ]);

    try {
        $kendaraan = $this->gudangStokService->prosesLansirGudangNested($request->all());
        
        return redirect()->route('gudang.lansir.show', $kendaraan->id)
            ->with('success', 'Lansir gudang berhasil disimpan dan stok telah dikurangi.');
    } catch (InsufficientStockException $e) {
        return redirect()->back()
            ->with('error', 'Stok tidak mencukupi. Tersedia: ' . $e->getMessage() . ' kg')
            ->withInput();
    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Gagal menyimpan: ' . $e->getMessage())
            ->withInput();
    }
}
```

### 5. View Create (🔄 TODO)

File: `resources/views/pages/gudang/lansir/create.blade.php`

**Structure:**
1. Header Form (Gudang, No Polisi, Sopir, Surat Jalan, Tanggal)
2. List Penerima (Dynamic)
   - Nama Penerima, Tujuan
   - List Pakan (Dynamic per penerima)
     - Kode Pakan, Jumlah KG, Karung (auto), Ongkos OA
   - List Tim Bongkar (Dynamic per penerima)
     - Nama Tim, Upah per KG

**JavaScript:**
- Clone penerima
- Clone pakan per penerima
- Clone tim per penerima
- Auto-calculate karung from KG
- Show available stock per pakan

### 6. View Show (🔄 TODO)

File: `resources/views/pages/gudang/lansir/show.blade.php`

**Display:**
- Kendaraan info (No Polisi, Sopir, Tanggal, Total)
- Table per Penerima:
  - Nama, Tujuan
  - Pakans (Kode, KG, Karung, Ongkos)
  - Tims (Nama, Upah)

### 7. View Index (🔄 TODO)

Update existing index to show new structure

### 8. Routes (🔄 TODO)

Already exists:
```php
Route::get('/lansir/create', [GudangLansirController::class, 'create'])->name('lansir.create');
Route::post('/lansir', [GudangLansirController::class, 'store'])->name('lansir.store');
Route::get('/lansir/{id}', [GudangLansirController::class, 'show'])->name('lansir.show');
```

### 9. Migration Commands

```bash
# Run migration
php artisan migrate

# If need rollback
php artisan migrate:rollback --step=2
```

### 10. Testing Checklist

- [ ] Create lansir with 1 penerima, 1 pakan
- [ ] Create lansir with multiple penerima
- [ ] Create lansir with multiple pakan per penerima
- [ ] Create lansir with tim bongkar
- [ ] Verify stock reduction
- [ ] Verify mutasi record
- [ ] Test insufficient stock error
- [ ] View show page
- [ ] View index page

## Next Steps

1. ✅ Run migration
2. 🔄 Implement controller methods
3. 🔄 Create view form
4. 🔄 Update show view
5. 🔄 Update index view
6. 🔄 Test all functionality
