# Fitur Logo Perusahaan

## Deskripsi
Menambahkan field logo pada manajemen perusahaan (CV) untuk menyimpan dan menampilkan logo perusahaan.

## Fitur yang Ditambahkan

### 1. Upload Logo
- Format yang didukung: JPG, PNG, GIF, SVG
- Maksimal ukuran file: 2MB
- Logo disimpan di folder `storage/app/public/logos/`
- Nama file otomatis dengan format: `logo_{timestamp}_{uniqid}.{ext}`

### 2. Preview Logo
- Preview real-time saat memilih file di form create
- Tampilan logo existing di form edit
- Preview logo baru saat mengubah logo di form edit

### 3. Hapus Logo
- Checkbox "Hapus logo" di form edit
- Logo lama otomatis dihapus saat upload logo baru
- Logo otomatis dihapus saat CV dihapus

## File yang Diubah/Ditambahkan

### Migration
1. **`database/migrations/2026_05_10_080000_add_logo_to_cv_table.php`** (BARU)
   - Menambahkan kolom `logo` (nullable) ke tabel `cv`

### Model
2. **`app/Models/Cv.php`**
   - Menambahkan `'logo'` ke `$fillable`

### Controller
3. **`app/Http/Controllers/Master/CvController.php`**
   - Import `Storage` facade
   - Update method `store()`: handle upload logo
   - Update method `update()`: handle upload/hapus logo
   - Update method `destroy()`: hapus logo saat CV dihapus

### Views
4. **`resources/views/pages/cv/create.blade.php`**
   - Tambah input file untuk logo
   - Tambah preview logo dengan JavaScript
   - Tambah `enctype="multipart/form-data"` pada form

5. **`resources/views/pages/cv/edit.blade.php`**
   - Tampilkan logo existing
   - Tambah input file untuk upload logo baru
   - Tambah checkbox "Hapus logo"
   - Tambah preview logo dengan JavaScript
   - Tambah `enctype="multipart/form-data"` pada form

## Cara Menggunakan

### 1. Jalankan Migration
```bash
php artisan migrate
```

### 2. Buat Symbolic Link untuk Storage (jika belum)
```bash
php artisan storage:link
```

Ini akan membuat symbolic link dari `public/storage` ke `storage/app/public`, sehingga file yang diupload bisa diakses via browser.

### 3. Tambah Perusahaan Baru dengan Logo
1. Buka menu **Pengaturan > Perusahaan**
2. Klik tombol **Tambah Perusahaan**
3. Isi form dan pilih file logo
4. Preview logo akan muncul otomatis
5. Klik **Simpan**

### 4. Edit Logo Perusahaan
1. Buka menu **Pengaturan > Perusahaan**
2. Klik tombol **Edit** pada perusahaan yang ingin diubah
3. Logo existing akan ditampilkan (jika ada)
4. Untuk mengubah logo:
   - Pilih file logo baru → logo lama akan otomatis terhapus
5. Untuk menghapus logo:
   - Centang checkbox "Hapus logo"
6. Klik **Update**

### 5. Menampilkan Logo di Template
Untuk menampilkan logo di template Blade atau PDF:

```blade
@if($cv->logo)
    <img src="{{ asset('storage/' . $cv->logo) }}" alt="Logo {{ $cv->nama_cv }}" style="max-height: 80px;">
@endif
```

Atau dengan pengecekan file exists:

```blade
@if($cv->logo && file_exists(public_path('storage/' . $cv->logo)))
    <img src="{{ asset('storage/' . $cv->logo) }}" alt="Logo {{ $cv->nama_cv }}" style="max-height: 80px;">
@else
    <span class="text-muted">Tidak ada logo</span>
@endif
```

## Struktur Folder

```
storage/
└── app/
    └── public/
        └── logos/              ← Logo perusahaan disimpan di sini
            ├── logo_1234567890_abc123.png
            ├── logo_1234567891_def456.jpg
            └── ...

public/
└── storage/                    ← Symbolic link ke storage/app/public
    └── logos/
```

## Validasi

### Create (store)
- `logo`: nullable, image, mimes:jpeg,png,jpg,gif,svg, max:2048 (KB)

### Update (update)
- `logo`: nullable, image, mimes:jpeg,png,jpg,gif,svg, max:2048 (KB)

## Keamanan

1. **Validasi Tipe File**: Hanya menerima file gambar (jpeg, png, jpg, gif, svg)
2. **Validasi Ukuran**: Maksimal 2MB per file
3. **Nama File Unik**: Menggunakan timestamp + uniqid untuk menghindari konflik
4. **Storage Disk**: Menggunakan disk 'public' yang aman
5. **Auto Delete**: Logo lama otomatis dihapus saat upload baru atau hapus CV

## Troubleshooting

### Logo tidak muncul setelah upload
1. Pastikan symbolic link sudah dibuat:
   ```bash
   php artisan storage:link
   ```
2. Cek permission folder storage:
   ```bash
   chmod -R 775 storage
   chmod -R 775 public/storage
   ```

### Error "The logo must be an image"
- Pastikan file yang diupload adalah file gambar valid
- Cek ekstensi file (harus jpeg, png, jpg, gif, atau svg)

### Error "The logo may not be greater than 2048 kilobytes"
- Ukuran file terlalu besar (> 2MB)
- Kompres gambar terlebih dahulu sebelum upload

## Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:
1. ✅ Crop/resize otomatis logo saat upload
2. ✅ Watermark pada logo
3. ✅ Multiple logo (logo utama, logo alternatif, favicon)
4. ✅ Tampilkan logo di header PDF export
5. ✅ Tampilkan logo di dashboard per CV
6. ✅ Galeri logo (history logo yang pernah digunakan)
