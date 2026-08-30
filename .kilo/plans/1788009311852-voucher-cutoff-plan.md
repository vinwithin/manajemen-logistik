# Rencana Perubahan Voucher Rugi Laba

## Keputusan Bisnis

- Voucher tidak lagi berlaku untuk PO dengan `tanggal_po >= 2026-08-29`.
- Voucher tetap berlaku untuk seluruh baris dari query `$pakansPo` dengan `tanggal_po < 2026-08-29`.
- `$pakansGudang` dan `$pakansTransfer` tidak menjadi dasar voucher.
- Nilai voucher lama tetap hardcode `0,5%`, tetapi hanya atas margin PO eligible:
  `jumlah_kg * (harga_pt_sum - ongkos_oa) * 0.005`.
- Karena voucher ditampilkan sebagai pos F terpisah, `potongan_voucher` tidak boleh ikut lagi dalam total C agar tidak dihitung ganda.

## Perubahan yang Diperlukan

1. **`app/Http/Controllers/RekapRugiLabaController.php`**
   - Tambahkan field tanggal PO pada select `$pakansPo` atau hitung nilai voucher langsung dari query PO terpisah.
   - Terapkan cutoff konstan `2026-08-29` menggunakan perbandingan tanggal yang eksplisit. Untuk laporan sebelum cutoff, seluruh PO dalam periode laporan eligible; untuk Agustus 2026, hanya PO tanggal 1-28 yang eligible; untuk periode setelah cutoff, tidak ada PO eligible.
   - Hitung `voucher` hanya dari baris PO eligible dengan rumus margin per baris, bukan dari `totalPenjualan` dan `totalPembelian` gabungan.
   - Kembalikan nilai voucher dari `hitungOtomatis()` agar index, detail, dan export menggunakan satu sumber perhitungan.
   - Pada `show()`, gunakan `$data['voucher']` hasil agregasi otomatis dan hitung:
     `labaBersih = labaKotor - totalBiayaOperasional - pph21 - voucher`.
   - Pada `index()`, gunakan voucher hasil helper yang sama.
   - Pada `export()`, gunakan voucher hasil helper yang sama dan jangan lagi mengurangi PPh dua kali.
   - Pastikan total biaya operasional yang dipakai untuk C tidak memasukkan `potongan_voucher`, atau buat accessor biaya operasional tanpa voucher. Pilihan minimal yang konsisten adalah menghapus `potongan_voucher` dari `getTotalBiayaOperasionalAttribute()` dan membiarkan section F memuat voucher otomatis.
   - Jangan gunakan `$rl->potongan_voucher` sebagai sumber voucher laporan setelah perubahan, karena nilai itu adalah input lama/database dan tidak memiliki relasi per PO.

2. **`app/Models/RugiLaba.php`**
   - Sesuaikan `getTotalBiayaOperasionalAttribute()` agar `potongan_voucher` tidak termasuk total C.
   - Pertahankan kolom/fillable/cast jika data historis masih dibutuhkan, tetapi jangan gunakan kolom itu dalam formula aktif.

3. **`resources/views/pages/keuangan/rugi-laba/show.blade.php`**
   - Pertahankan section F sebagai `Potongan Voucher`, tetapi tampilkan `$data['voucher']` yang sudah difilter per tanggal PO.
   - Pastikan label/rincian C tidak lagi menampilkan `potongan_voucher` sebagai biaya operasional, agar tabel sesuai dengan total C. Jika pos input lama perlu tetap terlihat untuk audit, beri label historis terpisah dan jangan ikut total.

4. **`app/Exports/RugiLabaExport.php`**
   - Tampilkan nilai voucher terfilter pada section F, bukan `$d['pph21']`.
   - Tampilkan nilai section C yang sama dengan `totalBiayaOperasional`; hindari format string yang menyebabkan penjumlahan teks seperti implementasi saat ini pada baris biaya otomatis. Format angka setelah penjumlahan nilai numerik.

5. **Sumber input manual/harian**
   - Putuskan dan dokumentasikan bahwa `potongan_voucher` lama tidak lagi mengendalikan laporan aktif. Jangan menghapus data database secara retroaktif.
   - Cek `syncTotalHarianKeRugiLaba()` agar sinkronisasi voucher harian tidak mengembalikan nilai lama ke total C atau menimpa hasil voucher otomatis. Kategori voucher dapat dipertahankan untuk histori, tetapi tidak dipakai pada formula baru.

## Konsistensi Rumus Akhir

- `labaKotor = totalPenjualan - totalPembelian`
- `pph21 = labaKotor > 0 ? labaKotor * 0.005 : 0`
- `voucher = Σ(margin setiap PO sebelum cutoff) * 0.005`
- `totalBiayaOperasional = biaya operasional manual tanpa voucher + biaya otomatis`
- `labaBersih = labaKotor - totalBiayaOperasional - pph21 - voucher`

Semua jalur index, detail, dan Excel harus menggunakan formula dan nilai voucher yang sama.

## Validasi

- Uji laporan Juli 2026: semua PO bulan itu eligible.
- Uji laporan Agustus 2026 dengan PO tanggal 28 dan 29: hanya PO tanggal 28 masuk dasar voucher.
- Uji laporan September 2026: voucher nol.
- PO dengan margin negatif dinyatakan tidak mungkin berdasarkan aturan bisnis, sehingga tidak diperlukan perlakuan khusus untuk kondisi tersebut.
- Uji bahwa `potongan_voucher` lama tidak menambah C sekaligus F.
- Uji parity angka laba bersih antara index, show, dan Excel.
- Uji laporan yang hanya memiliki gudang lansir/transfer: voucher nol.

## Asumsi Implementasi

- Setiap PO eligible memiliki margin non-negatif (`harga_pt_sum >= ongkos_oa`), sesuai kondisi bisnis yang disampaikan.
