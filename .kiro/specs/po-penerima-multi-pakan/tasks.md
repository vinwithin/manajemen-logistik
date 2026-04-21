# Implementation Plan: PO Penerima Multi-Pakan

## Overview

Merombak struktur data Purchase Order dari model berbasis kendaraan (`purchase_order_items`) menjadi
model berbasis penerima (`po_penerima` + `po_penerima_pakan`). Implementasi dilakukan secara inkremental:
migrasi skema → model baru → refactor controller → views → command migrasi data → integrasi OA.

Bahasa implementasi: **PHP (Laravel 11)** dengan Blade views dan Maatwebsite Excel.

---

## Tasks

- [x] 1. Database Migrations
  - [x] 1.1 Buat migration: buat tabel `po_penerima`
    - Buat file migration baru dengan kolom: `id`, `po_id` (FK → purchase_orders, CASCADE), `nama_penerima`, `tujuan_id` (FK nullable), `supplier_id` (FK nullable → suppliers, SET NULL), `no_polisi`, `nama_sopir`, `ongkos DECIMAL(15,2)`, `harga_pt_sum DECIMAL(15,2)`, `status ENUM('pending','berangkat','selesai','batal') DEFAULT 'pending'`, `timestamps`
    - _Requirements: 1.2, 4.1_

  - [x] 1.2 Buat migration: buat tabel `po_penerima_pakan`
    - Buat file migration baru dengan kolom: `id`, `po_penerima_id` (FK → po_penerima, CASCADE), `kode_pakan_id` (FK → kode_pakan, RESTRICT), `jumlah_kg DECIMAL(10,2)`, `jumlah_karung INT`, `timestamps`
    - Tambahkan unique constraint pada `(po_penerima_id, kode_pakan_id)`
    - _Requirements: 1.4, 1.6, 1.7_

  - [x] 1.3 Buat migration: tambah `po_penerima_id` ke tabel `oa_payments`
    - Buat file migration baru, tambahkan kolom `po_penerima_id BIGINT UNSIGNED NULL` dengan FK ke `po_penerima(id) ON DELETE SET NULL`
    - Pertahankan kolom `po_item_id` yang lama (backward compatibility)
    - _Requirements: 7.1, 7.4_

- [ ] 2. Model: `PoPenerimaPakan`
  - [x] 2.1 Buat `app/Models/PoPenerimaPakan.php`
    - Definisikan `$table = 'po_penerima_pakan'`, `$fillable` dengan semua kolom
    - Tambahkan relasi `penerima()` (BelongsTo PoPenerima) dan `kodePakan()` (BelongsTo KodePakan)
    - Implementasikan `booted()` dengan `static::saving()` yang menghitung `jumlah_karung = (int) ceil($model->jumlah_kg / 50)`
    - _Requirements: 1.4, 1.5_

  - [ ] 2.2 Tulis property test untuk `PoPenerimaPakan` — Property 1: jumlah_karung = ceil(jumlah_kg / 50)
    - **Property 1: Jumlah karung selalu ceil(jumlah_kg / 50)**
    - Gunakan `eris/eris` dengan `Generator\float(0.01, 100000.0)`, minimum 100 iterasi
    - Buat `PoPenerimaPakan` via `make()` dengan berbagai nilai `jumlah_kg`, assert `jumlah_karung === (int) ceil($kg / 50)`
    - **Validates: Requirements 1.5**

  - [ ]* 2.3 Tulis property test untuk `PoPenerimaPakan` — Property 2: round-trip consistency
    - **Property 2: PoPenerimaPakan round-trip**
    - Simpan record ke DB dan reload, assert semua field identik
    - **Validates: Requirements 1.4**

  - [ ]* 2.4 Tulis property test untuk `PoPenerimaPakan` — Property 3: duplikat kode pakan ditolak
    - **Property 3: Duplikat kode pakan per penerima ditolak**
    - Coba insert dua record dengan `(po_penerima_id, kode_pakan_id)` yang sama, assert exception/error
    - **Validates: Requirements 1.7**

- [ ] 3. Model: `PoPenerima`
  - [x] 3.1 Buat `app/Models/PoPenerima.php`
    - Definisikan `$table = 'po_penerima'`, `$fillable` dengan semua kolom termasuk `supplier_id`, `$casts` untuk `ongkos` dan `harga_pt_sum`
    - Definisikan konstanta `STATUSES` dan `VALID_TRANSITIONS`
    - Tambahkan relasi: `po()` (BelongsTo), `tujuan()` (BelongsTo), `supplier()` (BelongsTo Supplier via `supplier_id`), `pakans()` (HasMany PoPenerimaPakan), `oaPayment()` (HasOne OaPayment via `po_penerima_id`)
    - Implementasikan computed attributes: `getTotalKgAttribute()`, `getTotalOaAttribute()`, `getTotalPtSumAttribute()`
    - _Requirements: 1.2, 3.1, 3.2, 3.3, 4.1_

  - [ ]* 3.2 Tulis property test untuk `PoPenerima` — Property 4: total_kg adalah jumlah seluruh jumlah_kg
    - **Property 4: Total KG adalah jumlah seluruh jumlah_kg pakan**
    - Generate array of pakans dengan random `jumlah_kg`, assert `total_kg === sum(jumlah_kg)`
    - **Validates: Requirements 3.1**

  - [ ]* 3.3 Tulis property test untuk `PoPenerima` — Property 5: total_oa dan total_pt_sum independen
    - **Property 5: Total OA dan Total PT SUM dihitung independen per penerima**
    - Assert `total_oa === total_kg × ongkos` dan `total_pt_sum === total_kg × harga_pt_sum`
    - **Validates: Requirements 3.2, 3.3, 3.5**

  - [ ]* 3.4 Tulis property test untuk `PoPenerima` — Property 7: status awal selalu pending
    - **Property 7: Status awal penerima selalu pending**
    - Assert setiap `PoPenerima` baru memiliki `status === 'pending'`
    - **Validates: Requirements 4.1**

  - [ ]* 3.5 Tulis property test untuk `PoPenerima` — Property 8: transisi status valid
    - **Property 8: Transisi status mengikuti alur yang valid**
    - Untuk setiap status dan setiap target status, assert transisi valid diterima dan transisi invalid ditolak
    - **Validates: Requirements 4.2, 4.3**

  - [ ]* 3.6 Tulis property test untuk `PoPenerima` — Property 10: cascade delete
    - **Property 10: Cascade delete penerima menghapus semua pakan terkait**
    - Buat penerima dengan N pakans, hapus penerima, assert semua pakans terhapus
    - **Validates: Requirements 2.7**

- [ ] 4. Update Model: `PurchaseOrder`
  - [x] 4.1 Update `app/Models/PurchaseOrder.php`
    - Tambahkan relasi `penerimas()` (HasMany PoPenerima)
    - Update method `canLock()` untuk menggunakan `penerimas` (bukan `items`): return true jika semua penerima berstatus `selesai` atau `batal`
    - Hapus `supplier_id` dari `$fillable` dan hapus relasi `supplier()` jika ada
    - _Requirements: 1.1, 4.4, 4.5_

  - [ ]* 4.2 Tulis property test untuk `PurchaseOrder` — Property 6: grand total adalah jumlah total per penerima
    - **Property 6: Grand total adalah jumlah total per penerima**
    - Assert grand total OA = sum(total_oa per penerima), grand total PT SUM = sum(total_pt_sum per penerima)
    - **Validates: Requirements 3.4, 9.5**

  - [ ]* 4.3 Tulis property test untuk `PurchaseOrder` — Property 9: canLock() hanya true jika semua penerima terminal
    - **Property 9: Penguncian PO hanya diizinkan jika semua penerima terminal**
    - Assert `canLock() === true` hanya jika semua penerima berstatus `selesai` atau `batal`
    - **Validates: Requirements 4.4, 4.5**

- [x] 5. Update Model: `OaPayment`
  - [x] 5.1 Update `app/Models/OaPayment.php`
    - Tambahkan `po_penerima_id` ke `$fillable`
    - Tambahkan relasi `penerima()` (BelongsTo PoPenerima via `po_penerima_id`)
    - Pertahankan relasi `item()` yang lama
    - _Requirements: 7.1_

- [x] 6. Checkpoint — Pastikan semua model dan migrasi berjalan
  - Jalankan `php artisan migrate` dan pastikan tidak ada error
  - Jalankan unit test model: `php artisan test --filter PoPenerima`
  - Tanyakan ke user jika ada pertanyaan sebelum lanjut ke controller.

- [ ] 7. Refactor `PurchaseOrderController` — store() dan create()
  - [x] 7.1 Update method `create()` di `PurchaseOrderController`
    - Tambahkan `$suppliers = Supplier::orderBy('nama')->get()` ke data yang dikirim ke view (untuk dropdown per penerima)
    - Tambahkan `$kodePakans = KodePakan::orderBy('kode')->get()` ke data view
    - _Requirements: 2.1, 2.2_

  - [x] 7.2 Refactor method `store()` di `PurchaseOrderController`
    - Update validasi: hapus `supplier_id` dari header, tambahkan ke penerima: `penerima.*.supplier_id` (nullable, exists:suppliers,id), `penerima` array (min:1), `penerima.*.nama_penerima` (required), `penerima.*.no_polisi` (required), `penerima.*.ongkos` (numeric, min:0), `penerima.*.harga_pt_sum` (numeric, min:0), `penerima.*.pakans` (array, min:1), `penerima.*.pakans.*.kode_pakan_id` (required, exists), `penerima.*.pakans.*.jumlah_kg` (required, numeric, min:0.01)
    - Dalam `DB::transaction()`: buat `PurchaseOrder` tanpa `supplier_id`, loop penerima → buat `PoPenerima` (dengan `supplier_id`, status='pending'), loop pakans → buat `PoPenerimaPakan`
    - Tangkap unique constraint violation dan kembalikan sebagai validation error
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 1.1, 1.2, 1.4_

- [ ] 8. Refactor `PurchaseOrderController` — edit(), update(), show()
  - [x] 8.1 Update method `edit()` di `PurchaseOrderController`
    - Update eager loading: `with(['cv', 'penerimas.pakans.kodePakan', 'penerimas.tujuan', 'penerimas.supplier'])`
    - Kirim `$suppliers` dan `$kodePakans` ke view (untuk dropdown per penerima)
    - _Requirements: 2.5, 5.2_

  - [x] 8.2 Refactor method `update()` di `PurchaseOrderController`
    - Update validasi untuk struktur penerima baru (sama dengan store, tambah `penerima.*.id` optional untuk existing; `supplier_id` ada di level penerima, bukan header)
    - Dalam transaction: update header PO (tanpa `supplier_id`), sync penerimas (hapus yang tidak ada di request, update yang ada termasuk `supplier_id`, buat yang baru), sync pakans per penerima
    - Tolak update jika PO locked
    - _Requirements: 2.5, 2.6, 2.7_

  - [x] 8.3 Update method `show()` di `PurchaseOrderController`
    - Update eager loading: `with(['cv', 'penerimas.pakans.kodePakan', 'penerimas.tujuan', 'penerimas.supplier', 'penerimas.oaPayment'])`
    - Hitung kolom kode pakan dinamis untuk pivot display
    - Hitung ringkasan per mobil (group by no_polisi)
    - _Requirements: 5.2, 5.3_

- [ ] 9. Refactor `PurchaseOrderController` — lock() dan exportPo()
  - [x] 9.1 Update method `lock()` di `PurchaseOrderController`
    - Ganti `with('items')` menjadi `with('penerimas')`
    - Update pesan error: tampilkan jumlah penerima yang belum selesai
    - _Requirements: 4.4, 4.5_

  - [x] 9.2 Refactor method `exportPo()` dan `PurchaseOrderExport`
    - Update `app/Exports/PurchaseOrderExport.php`: load `penerimas.pakans.kodePakan` dan `penerimas.tujuan`
    - Buat kolom dinamis berdasarkan kode pakan yang ada di PO (pivot)
    - Urutkan baris berdasarkan `no_polisi`
    - Tambahkan baris subtotal per kode pakan di bagian bawah
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [ ]* 9.3 Tulis property test untuk export — Property 12: kolom dinamis sesuai kode pakan
    - **Property 12: Ekspor Excel memiliki kolom dinamis sesuai kode pakan dalam PO**
    - Assert jumlah kolom = N kode pakan + 4, subtotal row = column-wise sum
    - **Validates: Requirements 8.1, 8.3**

  - [ ]* 9.4 Tulis property test untuk export — Property 13: urutan baris berdasarkan no_polisi
    - **Property 13: Urutan baris ekspor berdasarkan no_polisi**
    - Assert baris diurutkan ascending by no_polisi
    - **Validates: Requirements 8.2**

- [x] 10. Buat `RekapPoController` dan `RekapPoExport`
  - [x] 10.1 Buat `app/Http/Controllers/RekapPoController.php`
    - Method `show(string $id)`: load PO dengan `penerimas.pakans`, hitung `total_kg`, `total_oa`, `total_pt_sum` per penerima, hitung grand total OA dan grand total PT SUM, kirim ke view
    - Method `export(string $id)`: download Excel via `RekapPoExport`
    - _Requirements: 9.1, 9.2, 9.3_

  - [x] 10.2 Buat `app/Exports/RekapPoExport.php`
    - Implementasikan dua sheet/tabel: "Rekap Supplier (OA)" dan "Rekap PT SUM"
    - Sheet OA: kolom # | Nama Penerima | Total KG | Ongkos/kg | Total OA, dengan grand total
    - Sheet PT SUM: kolom # | Nama Penerima | Total KG | Harga PT SUM/kg | Total PT SUM, dengan grand total
    - _Requirements: 9.4, 9.5_

- [x] 11. Update `RekapOaController`
  - [x] 11.1 Refactor `RekapOaController` untuk menggunakan `PoPenerima`
    - Update query di `index()`: ganti `PurchaseOrderItem` dengan `PoPenerima`, update filter dan kolom DataTables
    - Update `bayar()`: load `PoPenerima` dengan relasi `po.cv`, `tujuan`, `oaPayment`; hitung tagihan dari `total_oa`
    - Update `storeBayar()`: gunakan `po_penerima_id` sebagai key untuk `OaPayment::updateOrCreate()`
    - _Requirements: 7.1, 7.2, 7.3_

- [x] 12. Tambah Routes
  - [x] 12.1 Update `routes/web.php`
    - Tambahkan route untuk `RekapPoController`: `GET /purchase-order/{id}/rekap-po` (name: `rekap-po.show`) dan `GET /purchase-order/{id}/rekap-po/export` (name: `rekap-po.export`)
    - Tambahkan import `use App\Http\Controllers\RekapPoController;`
    - _Requirements: 9.1, 9.4_

- [x] 13. Views — `create.blade.php`
  - [x] 13.1 Redesign `resources/views/pages/purchase-order/create.blade.php`
    - Hapus field `supplier_id` dari header form
    - Ganti area item lama dengan area penerima dinamis: setiap baris penerima memiliki field `nama_penerima`, `tujuan_id`, `supplier_id` (dropdown), `no_polisi`, `nama_sopir`, `ongkos`, `harga_pt_sum`
    - Setiap baris penerima memiliki sub-area kode pakan dinamis: select `kode_pakan_id` + input `jumlah_kg` + display `jumlah_karung` (readonly, auto-calc)
    - Tombol "+ Tambah Penerima" dan "+ Tambah Pakan" per penerima
    - JS: clone template penerima/pakan, update index, auto-calc `jumlah_karung = Math.ceil(kg / 50)`, validasi client-side minimal 1 penerima dan 1 pakan per penerima
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 14. Views — `edit.blade.php`
  - [x] 14.1 Redesign `resources/views/pages/purchase-order/edit.blade.php`
    - Struktur identik dengan `create.blade.php` (penerima + pakan dinamis, supplier dropdown per penerima)
    - Tambahkan badge status per penerima
    - Tambahkan dropdown status per penerima (hanya tampilkan transisi valid sesuai `VALID_TRANSITIONS`)
    - Tambahkan tombol Lock/Unlock di header
    - Jika PO locked: semua field readonly, tampilkan alert "PO sudah dikunci"
    - _Requirements: 2.5, 2.6, 4.2, 4.3_

- [x] 15. Views — `show.blade.php`
  - [x] 15.1 Redesign `resources/views/pages/purchase-order/show.blade.php`
    - Header PO: tampilkan no_po, tanggal, CV, status, catatan (tanpa supplier di header)
    - Tabel penerima dengan kolom dinamis kode pakan (pivot): setiap kode pakan unik dalam PO menjadi satu kolom, nilai = `jumlah_karung` penerima untuk kode pakan tersebut
    - Kolom tetap: #, Nama Penerima, Tujuan, Supplier, [kode pakan cols...], Plat, Sopir, Ongkos/kg, Harga PT SUM/kg, Total KG, Total OA, Total PT SUM, Status
    - Ringkasan per mobil: tabel group by `no_polisi` dengan daftar penerima dan total kg per mobil
    - Tombol Export Excel dan Rekap PO
    - _Requirements: 5.2, 5.3, 3.4_

- [x] 16. Views — `rekap-po/show.blade.php`
  - [x] 16.1 Buat `resources/views/pages/keuangan/rekap-po/show.blade.php`
    - Bagian 1 — Rekap Supplier (OA): tabel # | Nama Penerima | Total KG | Ongkos/kg | Total OA | Status Bayar, grand total OA
    - Bagian 2 — Rekap PT SUM: tabel # | Nama Penerima | Total KG | Harga PT SUM/kg | Total PT SUM, grand total PT SUM
    - Dua bagian terpisah secara visual (card/section berbeda)
    - Tombol Export Excel
    - _Requirements: 9.1, 9.2, 9.3_

- [x] 17. Checkpoint — Pastikan semua controller dan views berjalan
  - Jalankan `php artisan test` dan pastikan tidak ada error
  - Verifikasi halaman create, edit, show PO dapat diakses tanpa error
  - Tanyakan ke user jika ada pertanyaan sebelum lanjut ke migrasi data.

- [x] 18. Artisan Command: `MigratePoStrukturCommand`
  - [x] 18.1 Buat `app/Console/Commands/MigratePoStrukturCommand.php`
    - Signature: `po:migrate-struktur {--dry-run}`
    - Untuk setiap `purchase_order_items`: buat record `po_penerima` (map field: `po_id`, `nama_penerima`, `tujuan_id`, `ongkos`, `no_polisi`, `nama_supir`→`nama_sopir`, `status`, `supplier_id` dari item langsung ke `po_penerima.supplier_id`; `harga_pt_sum = 0`)
    - Jika item memiliki `kode_pakan_id` dan `berat` tidak null: buat `po_penerima_pakan` (`berat`→`jumlah_kg`, hitung ulang `jumlah_karung`)
    - Update `oa_payments.po_penerima_id` berdasarkan `po_item_id` yang bersesuaian
    - Verifikasi: `sum(po_penerima_pakan.jumlah_kg) == sum(items.berat where kode_pakan_id not null)`, log hasilnya
    - Jika `--dry-run`: tampilkan ringkasan tanpa eksekusi
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 7.4_

  - [ ]* 18.2 Tulis property test untuk migrasi — Property 11: total kg dipertahankan
    - **Property 11: Migrasi mempertahankan total kg**
    - Buat set `purchase_order_items` dengan random `berat`, jalankan command, assert `sum(po_penerima_pakan.jumlah_kg) === sum(items.berat)`
    - **Validates: Requirements 6.4**

- [x] 19. Final Checkpoint — Pastikan semua fitur terintegrasi
  - Jalankan `php artisan test` dan pastikan semua test pass
  - Jalankan `php artisan po:migrate-struktur --dry-run` dan verifikasi output
  - Tanyakan ke user jika ada pertanyaan sebelum dinyatakan selesai.

---

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Tabel lama (`purchase_order_items`, `po_item_penerima`) dipertahankan untuk backward compatibility — jangan hapus
- Setiap task mereferensikan requirements spesifik untuk traceability
- Property tests menggunakan library `eris/eris` dengan minimum 100 iterasi per property
- Checkpoint memastikan validasi inkremental sebelum lanjut ke fase berikutnya
