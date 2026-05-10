# Instruksi Menjalankan Migration DP Supplier

## Masalah
Jika Anda mendapat error saat create PO setelah menambahkan fitur DP, kemungkinan besar migration belum dijalankan.

## Solusi

### 1. Jalankan Migration
Buka terminal dan jalankan perintah berikut:

```bash
cd /Users/macbook/hrz-group/hrz-group
php artisan migrate
```

### 2. Jika Migration Sudah Pernah Dijalankan
Jika migration sudah pernah dijalankan tapi masih error, coba jalankan migration spesifik:

```bash
php artisan migrate --path=database/migrations/2026_05_10_090000_add_dp_fields_to_po_kendaraan.php
```

### 3. Cek Status Migration
Untuk memastikan migration sudah berjalan:

```bash
php artisan migrate:status
```

Cari baris yang berisi `2026_05_10_090000_add_dp_fields_to_po_kendaraan`, pastikan statusnya **Ran**.

### 4. Jika Masih Error
Jika masih error setelah migration, cek:

1. **Cek kolom di database:**
   ```sql
   DESCRIBE po_kendaraan;
   ```
   
   Pastikan ada kolom:
   - `dp_nominal`
   - `dp_persen`
   - `dp_tanggal`
   - `dp_metode`
   - `dp_keterangan`

2. **Cek Laravel log:**
   ```bash
   tail -50 storage/logs/laravel.log
   ```

3. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

## Error Message yang Mungkin Muncul

### "Unknown column 'dp_nominal'"
**Penyebab:** Migration belum dijalankan.
**Solusi:** Jalankan `php artisan migrate`

### "Database belum diupdate"
**Penyebab:** Migration belum dijalankan.
**Solusi:** Jalankan `php artisan migrate`

### "SQLSTATE[42S22]: Column not found"
**Penyebab:** Migration belum dijalankan atau gagal.
**Solusi:** 
1. Jalankan `php artisan migrate`
2. Jika gagal, cek error di terminal
3. Perbaiki error (biasanya koneksi database)
4. Jalankan lagi

## Verifikasi Setelah Migration

1. **Buka halaman Create PO**
2. **Tambah kendaraan**
3. **Isi data DP** (nominal, tanggal, metode)
4. **Submit form**
5. **Pastikan tidak ada error**

Jika masih ada masalah, cek file log di `storage/logs/laravel.log` untuk detail error.
