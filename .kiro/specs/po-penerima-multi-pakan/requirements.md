# Requirements Document

## Introduction

Fitur ini merombak struktur data Purchase Order (PO) pakan ternak pada sistem berbasis Laravel 11.
Struktur lama mengorganisir PO per kendaraan (satu item = satu mobil), sehingga tidak bisa merepresentasikan
kenyataan di lapangan: satu mobil bisa mengangkut pakan untuk beberapa peternak sekaligus, dan ongkos angkut
dihitung per penerima (bukan per mobil).

Struktur baru mengorganisir PO per **penerima**: setiap baris PO mewakili satu peternak/penerima, dengan
daftar kode pakan dan jumlahnya masing-masing. Satu mobil (no_polisi) bisa muncul di beberapa baris penerima.
Ongkos/OA dihitung per penerima berdasarkan total berat pakan yang diterima × ongkos per kg.

Migrasi data dari struktur lama ke struktur baru juga termasuk dalam scope fitur ini.

---

## Glossary

- **PO (Purchase Order)**: Dokumen pembelian pakan ternak yang berisi daftar penerima dan pakan yang dipesan.
- **Penerima**: Peternak atau entitas yang menerima pakan. Satu PO bisa memiliki banyak penerima.
- **Kode Pakan**: Kode produk pakan ternak (contoh: S00, S11, S12G). Didefinisikan di tabel `kode_pakan`.
- **Tujuan**: Lokasi pengiriman pakan. Didefinisikan di tabel `tujuan`.
- **Supplier**: Pemasok pakan. Setiap penerima/mobil memiliki suppliernya sendiri.
- **CV**: Perusahaan/entitas pemilik PO. Didefinisikan di tabel `cv`.
- **Ongkos**: Biaya angkut per kilogram (Rp/kg) yang dibebankan per penerima — digunakan untuk pembayaran ke supplier/OA.
- **Harga_PT_SUM**: Harga per kilogram (Rp/kg) yang ditagihkan ke PT SUM per penerima — terpisah dari ongkos ke supplier.
- **OA (Ongkos Angkut)**: Total biaya angkut untuk satu penerima ke supplier = total_kg × ongkos.
- **Total_PT_SUM**: Total tagihan ke PT SUM untuk satu penerima = total_kg × harga_pt_sum.
- **Karung**: Satuan kemasan pakan. 1 karung = 50 kg.
- **No Polisi**: Nomor plat kendaraan pengantar pakan.
- **PO_System**: Sistem manajemen PO pakan ternak berbasis Laravel 11 yang sedang dikembangkan.
- **PO_Penerima**: Entitas baris penerima dalam satu PO, mewakili satu peternak beserta detail pengirimannya.
- **PO_Penerima_Pakan**: Entitas detail kode pakan dan jumlah untuk satu PO_Penerima.
- **Status_PO**: Kondisi PO: `draft` (masih bisa diedit) atau `locked` (sudah final).
- **Status_Penerima**: Kondisi pengiriman per penerima: `pending`, `berangkat`, `selesai`, `batal`.

---

## Requirements

### Requirement 1: Struktur Data PO Baru

**User Story:** Sebagai operator, saya ingin membuat PO dengan daftar penerima beserta kode pakan masing-masing,
sehingga satu PO bisa merepresentasikan pengiriman ke banyak peternak sekaligus.

#### Acceptance Criteria

1. THE PO_System SHALL menyimpan setiap PO dengan field: `no_po`, `tanggal_po`, `cv_id`, `status`, dan `catatan`.
2. THE PO_System SHALL menyimpan setiap PO_Penerima dengan field: `po_id`, `nama_penerima`, `tujuan_id`, `supplier_id`, `ongkos` (Rp/kg), `harga_pt_sum` (Rp/kg), `no_polisi`, `nama_sopir`, dan `status`.
3. THE PO_System SHALL memungkinkan satu nilai `no_polisi` yang sama muncul pada lebih dari satu PO_Penerima dalam PO yang sama.
4. THE PO_System SHALL menyimpan setiap PO_Penerima_Pakan dengan field: `po_penerima_id`, `kode_pakan_id`, `jumlah_kg`, dan `jumlah_karung`.
5. WHEN nilai `jumlah_kg` disimpan, THE PO_System SHALL menghitung `jumlah_karung` sebagai `jumlah_kg / 50` (dibulatkan ke atas).
6. THE PO_System SHALL memungkinkan satu PO_Penerima memiliki lebih dari satu PO_Penerima_Pakan dengan kode pakan yang berbeda.
7. IF `kode_pakan_id` yang sama didaftarkan dua kali pada PO_Penerima yang sama, THEN THE PO_System SHALL menolak penyimpanan dan mengembalikan pesan error validasi.

---

### Requirement 2: Pembuatan dan Pengeditan PO

**User Story:** Sebagai operator, saya ingin membuat dan mengedit PO beserta daftar penerima dan pakannya melalui antarmuka web,
sehingga data PO dapat dikelola dengan mudah.

#### Acceptance Criteria

1. WHEN operator mengakses halaman buat PO, THE PO_System SHALL menampilkan form dengan field header PO (no_po, tanggal_po, cv_id, catatan) dan area untuk menambah baris penerima secara dinamis.
2. WHEN operator menambah baris penerima, THE PO_System SHALL menampilkan field: nama_penerima, tujuan_id, supplier_id, ongkos, harga_pt_sum, no_polisi, nama_sopir, dan area untuk menambah kode pakan beserta jumlah_kg.
3. WHEN operator menyimpan PO baru, THE PO_System SHALL memvalidasi bahwa: no_po tidak kosong dan unik, tanggal_po valid, cv_id ada di database, minimal satu penerima ditambahkan, setiap penerima memiliki minimal satu kode pakan dengan jumlah_kg > 0, dan nilai `ongkos` serta `harga_pt_sum` adalah angka non-negatif.
4. IF validasi gagal saat menyimpan PO, THEN THE PO_System SHALL menampilkan pesan error spesifik per field yang tidak valid tanpa menghapus data yang sudah diisi operator.
5. WHILE status PO adalah `draft`, THE PO_System SHALL mengizinkan operator mengubah semua field header PO dan daftar penerima.
6. WHILE status PO adalah `locked`, THE PO_System SHALL menolak perubahan data PO dan menampilkan pesan bahwa PO sudah dikunci.
7. WHEN operator menghapus baris penerima dari PO berstatus `draft`, THE PO_System SHALL menghapus PO_Penerima beserta seluruh PO_Penerima_Pakan terkait.

---

### Requirement 3: Kalkulasi OA (Ongkos Angkut) dan Tagihan PT SUM per Penerima

**User Story:** Sebagai operator keuangan, saya ingin OA ke supplier dan tagihan ke PT SUM dihitung otomatis per penerima berdasarkan total berat dan harga masing-masing,
sehingga kedua tagihan akurat per peternak.

#### Acceptance Criteria

1. WHEN data PO_Penerima ditampilkan, THE PO_System SHALL menghitung dan menampilkan `total_kg` sebagai jumlah seluruh `jumlah_kg` dari PO_Penerima_Pakan milik penerima tersebut.
2. WHEN data PO_Penerima ditampilkan, THE PO_System SHALL menghitung dan menampilkan `total_oa` sebagai `total_kg × ongkos`.
3. WHEN data PO_Penerima ditampilkan, THE PO_System SHALL menghitung dan menampilkan `total_pt_sum` sebagai `total_kg × harga_pt_sum`.
4. WHEN halaman detail PO ditampilkan, THE PO_System SHALL menampilkan subtotal OA dan subtotal PT SUM per penerima, serta grand total OA dan grand total PT SUM seluruh penerima dalam PO tersebut.
5. THE PO_System SHALL menghitung OA dan total_pt_sum secara independen per penerima, tanpa mempengaruhi atau dipengaruhi oleh penerima lain yang menggunakan mobil yang sama.

---

### Requirement 4: Manajemen Status Penerima dan Penguncian PO

**User Story:** Sebagai operator, saya ingin melacak status pengiriman per penerima dan mengunci PO ketika semua pengiriman selesai,
sehingga data PO yang sudah final tidak bisa diubah secara tidak sengaja.

#### Acceptance Criteria

1. THE PO_System SHALL menetapkan status awal setiap PO_Penerima sebagai `pending` saat pertama kali dibuat.
2. WHEN status PO_Penerima diubah, THE PO_System SHALL memvalidasi bahwa transisi status mengikuti alur: `pending` → `berangkat` → `selesai`, atau `pending`/`berangkat` → `batal`.
3. IF transisi status tidak valid dilakukan, THEN THE PO_System SHALL menolak perubahan dan mengembalikan pesan error yang menjelaskan transisi yang diizinkan.
4. WHEN semua PO_Penerima dalam satu PO berstatus `selesai` atau `batal`, THE PO_System SHALL mengizinkan operator mengunci PO (mengubah status PO menjadi `locked`).
5. IF masih ada PO_Penerima berstatus `pending` atau `berangkat`, THEN THE PO_System SHALL menolak penguncian PO dan menampilkan jumlah penerima yang belum selesai.

---

### Requirement 5: Tampilan Daftar dan Detail PO

**User Story:** Sebagai operator, saya ingin melihat daftar PO dan detail setiap PO termasuk semua penerima dan pakannya,
sehingga informasi pengiriman mudah dipantau.

#### Acceptance Criteria

1. WHEN operator mengakses halaman daftar PO, THE PO_System SHALL menampilkan tabel dengan kolom: no_po, tanggal_po, nama CV, jumlah penerima, total kg, total OA, dan status PO.
2. WHEN operator mengakses halaman detail PO, THE PO_System SHALL menampilkan header PO dan tabel penerima dengan kolom: no urut, nama_penerima, tujuan, kode pakan (beserta jumlah kg dan karung per kode), no_polisi, nama_sopir, ongkos/kg, harga_pt_sum/kg, total_kg, total_oa, total_pt_sum, dan status.
3. WHEN operator mengakses halaman detail PO, THE PO_System SHALL menampilkan ringkasan per mobil (no_polisi) yang menunjukkan daftar penerima yang diangkut dan total kg per mobil.
4. THE PO_System SHALL mendukung pencarian dan filter daftar PO berdasarkan: no_po, tanggal_po (rentang), nama supplier, nama CV, dan status PO menggunakan Yajra DataTables.

---

### Requirement 6: Migrasi Data dari Struktur Lama

**User Story:** Sebagai administrator sistem, saya ingin data PO yang sudah ada dimigrasikan ke struktur baru secara otomatis,
sehingga tidak ada data historis yang hilang.

#### Acceptance Criteria

1. WHEN migrasi dijalankan, THE PO_System SHALL membuat tabel `po_penerima` dan `po_penerima_pakan` sesuai struktur baru.
2. WHEN migrasi dijalankan, THE PO_System SHALL memindahkan setiap record `purchase_order_items` lama menjadi satu record `po_penerima` baru dengan memetakan field: `po_id`, `nama_penerima`, `tujuan_id`, `ongkos`, `no_polisi`, `nama_supir` → `nama_sopir`, `status`, dan `supplier_id` dari `purchase_order_items` langsung ke `po_penerima.supplier_id`. Nilai `harga_pt_sum` akan diisi `0` sebagai default untuk data lama.
3. WHEN migrasi dijalankan, THE PO_System SHALL membuat satu record `po_penerima_pakan` untuk setiap `purchase_order_items` lama yang memiliki `kode_pakan_id` dan `berat` tidak null, dengan memetakan `berat` → `jumlah_kg` dan menghitung ulang `jumlah_karung`.
4. WHEN migrasi selesai, THE PO_System SHALL memverifikasi bahwa jumlah total `jumlah_kg` pada struktur baru sama dengan jumlah total `berat` pada struktur lama, dan mencatat hasilnya di log migrasi.
5. THE PO_System SHALL menyediakan perintah artisan `php artisan po:migrate-struktur` untuk menjalankan migrasi data dengan opsi `--dry-run` yang menampilkan ringkasan perubahan tanpa mengeksekusi.

---

### Requirement 7: Integrasi dengan Modul OA Payment

**User Story:** Sebagai operator keuangan, saya ingin pembayaran OA tetap bisa dilacak per penerima setelah perombakan struktur,
sehingga rekap keuangan tetap akurat.

#### Acceptance Criteria

1. THE PO_System SHALL mengasosiasikan setiap record `oa_payments` dengan `po_penerima_id` (bukan `po_item_id` seperti sebelumnya).
2. WHEN halaman rekap OA ditampilkan, THE PO_System SHALL mengelompokkan tagihan OA per penerima berdasarkan `po_penerima_id`.
3. WHEN status pembayaran OA diperbarui, THE PO_System SHALL memperbarui status pada record `oa_payments` yang terkait dengan `po_penerima_id` yang sesuai.
4. WHEN migrasi data dijalankan, THE PO_System SHALL memperbarui referensi `po_item_id` pada tabel `oa_payments` yang sudah ada menjadi `po_penerima_id` yang bersesuaian.

---

### Requirement 8: Ekspor Data PO

**User Story:** Sebagai operator, saya ingin mengekspor data PO ke format Excel sesuai format dokumen fisik yang ada,
sehingga dokumen digital sesuai dengan dokumen lapangan.

#### Acceptance Criteria

1. WHEN operator mengekspor PO ke Excel, THE PO_System SHALL menghasilkan file dengan kolom: NO, PETERNAK (nama_penerima), kolom per kode pakan (jumlah karung), PLAT (no_polisi), KETERANGAN (tujuan).
2. WHEN mengekspor PO, THE PO_System SHALL mengurutkan baris berdasarkan `no_polisi` sehingga penerima dengan mobil yang sama berurutan.
3. WHEN mengekspor PO, THE PO_System SHALL menampilkan baris subtotal per kode pakan di bagian bawah tabel.
4. THE PO_System SHALL mendukung ekspor menggunakan library Maatwebsite Excel yang sudah terpasang.

---

### Requirement 9: Rekapitulasi Dua Harga (Rekap Supplier/OA dan Rekap PT SUM)

**User Story:** Sebagai operator keuangan, saya ingin melihat dua rekap terpisah — satu untuk tagihan ke supplier (OA) dan satu untuk tagihan ke PT SUM — sehingga kedua pihak bisa ditagih dengan nilai yang benar.

#### Acceptance Criteria

1. WHEN operator mengakses halaman rekap PO, THE PO_System SHALL menampilkan **Rekap Supplier (OA)** yang berisi: daftar penerima, total_kg per penerima, ongkos/kg, dan `total_oa` (total_kg × ongkos) per penerima, serta grand total OA seluruh penerima.
2. WHEN operator mengakses halaman rekap PO, THE PO_System SHALL menampilkan **Rekap PT SUM** yang berisi: daftar penerima, total_kg per penerima, harga_pt_sum/kg, dan `total_pt_sum` (total_kg × harga_pt_sum) per penerima, serta grand total PT SUM seluruh penerima.
3. THE PO_System SHALL menampilkan Rekap Supplier (OA) dan Rekap PT SUM sebagai dua bagian yang terpisah secara visual pada halaman yang sama.
4. WHEN operator mengekspor rekap ke Excel, THE PO_System SHALL menghasilkan dua sheet atau dua tabel terpisah: satu untuk Rekap Supplier (OA) dan satu untuk Rekap PT SUM.
5. THE PO_System SHALL menghitung grand total Rekap Supplier (OA) dan grand total Rekap PT SUM secara independen satu sama lain.
