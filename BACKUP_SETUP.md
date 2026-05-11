# Database Backup Setup

## Fitur Backup Database Otomatis

Aplikasi ini dilengkapi dengan fitur backup database otomatis yang akan berjalan setiap hari pada pukul 02:00 WIB.

## Cara Kerja

- Backup database menggunakan `mysqldump`
- File backup dikompress dengan `gzip` untuk menghemat ruang penyimpanan
- Backup lama akan otomatis dihapus (default: simpan 7 hari terakhir)
- File backup disimpan di: `storage/app/backups/`
- Format nama file: `backup_[nama-database]_[tanggal]_[waktu].sql.gz`

## Setup Cron Job di Server

Untuk mengaktifkan backup otomatis, tambahkan cron job berikut di server:

### 1. Buka crontab editor
```bash
crontab -e
```

### 2. Tambahkan baris berikut
```bash
# Laravel Scheduler (termasuk backup database)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**Penting:** Ganti `/path/to/your/project` dengan path lengkap ke folder project Anda.

Contoh:
```bash
* * * * * cd /var/www/hrz-group && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Simpan dan keluar
- Untuk nano: tekan `Ctrl+X`, lalu `Y`, lalu `Enter`
- Untuk vi/vim: tekan `Esc`, ketik `:wq`, lalu `Enter`

## Menjalankan Backup Manual

Anda juga bisa menjalankan backup secara manual kapan saja:

```bash
# Backup dengan pengaturan default (simpan 7 hari)
php artisan db:backup

# Backup dengan custom jumlah hari penyimpanan
php artisan db:backup --keep-days=30
```

## Melihat Daftar Backup

```bash
ls -lh storage/app/backups/
```

## Restore Database dari Backup

Untuk restore database dari file backup:

```bash
# Extract file backup terlebih dahulu
gunzip storage/app/backups/backup_hrz-group-db_2026-05-11_141215.sql.gz

# Restore ke database
mysql -u root -p hrz-group-db < storage/app/backups/backup_hrz-group-db_2026-05-11_141215.sql
```

## Konfigurasi

### Mengubah Jadwal Backup

Edit file `routes/console.php`:

```php
// Backup setiap hari jam 2 pagi (default)
Schedule::command('db:backup')->dailyAt('02:00');

// Atau ubah sesuai kebutuhan:
Schedule::command('db:backup')->dailyAt('03:00'); // Jam 3 pagi
Schedule::command('db:backup')->twiceDaily(2, 14); // Jam 2 pagi dan 2 siang
Schedule::command('db:backup')->weekly(); // Setiap minggu
```

### Mengubah Jumlah Hari Penyimpanan Backup

Edit file `routes/console.php`:

```php
// Simpan backup 30 hari
Schedule::command('db:backup --keep-days=30')->dailyAt('02:00');
```

## Troubleshooting

### Error: mysqldump command not found
Pastikan MySQL client tools sudah terinstall di server.

**Ubuntu/Debian:**
```bash
sudo apt-get install mysql-client
```

**CentOS/RHEL:**
```bash
sudo yum install mysql
```

### Error: Permission denied
Pastikan folder `storage/app/backups` memiliki permission yang benar:
```bash
chmod -R 775 storage/app/backups
chown -R www-data:www-data storage/app/backups
```

### Cron tidak berjalan
Periksa apakah cron service berjalan:
```bash
sudo systemctl status cron
```

Periksa log cron:
```bash
grep CRON /var/log/syslog
```

## Monitoring

Backup akan mencatat log di `storage/logs/laravel.log`. Anda bisa memonitor dengan:

```bash
tail -f storage/logs/laravel.log | grep backup
```

## Keamanan

- File backup **TIDAK** di-commit ke Git (sudah ditambahkan ke `.gitignore`)
- Pastikan folder `storage/app/backups` tidak bisa diakses dari web
- Pertimbangkan untuk menyimpan backup di lokasi terpisah atau cloud storage untuk keamanan ekstra
