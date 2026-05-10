# Quick Reference: DP & OA Payment per Kendaraan

## 🚀 Quick Start

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Test Flow
1. Create PO dengan DP → Lihat 1 record di `oa_payments`
2. Buka Rekap OA → Lihat kendaraan (bukan penerima)
3. Klik "Bayar" → Input pembayaran cicilan
4. Verify status berubah (pending → partial → lunas)

---

## 📊 Data Structure

### OaPayment Record (per Kendaraan)
```
po_kendaraan_id: 123
po_penerima_id: NULL ← PENTING!
tipe_pembayaran: 'oa'
jumlah_tagihan: 5000000 (total OA dari semua penerima)
jumlah_bayar: 1000000 (DP + cicilan)
status: 'partial'
```

### PoKendaraan Record
```
id: 123
dp_nominal: 1000000
dp_persen: 20
dp_tanggal: 2026-05-10
dp_metode: 'transfer'
dp_keterangan: 'DP 20%'
```

---

## 🔍 Key Differences

| Aspect | Before (per Penerima) | After (per Kendaraan) |
|--------|----------------------|----------------------|
| **Records** | 3 penerima = 3 records | 3 penerima = 1 record |
| **po_penerima_id** | NOT NULL | NULL |
| **po_kendaraan_id** | Optional | Required |
| **jumlah_tagihan** | OA 1 penerima | Total OA semua penerima |
| **DP** | Di penerima pertama | Di kendaraan |
| **Query** | `PoPenerima::with(...)` | `PoKendaraan::with(...)` |

---

## 💡 Common Queries

### Get OA Payment for Kendaraan
```php
$payment = OaPayment::where('po_kendaraan_id', $kendaraanId)
    ->where('tipe_pembayaran', 'oa')
    ->first();
```

### Get Total Tagihan Kendaraan
```php
$kendaraan = PoKendaraan::with('penerimas.pakans')->find($id);
$totalTagihan = $kendaraan->total_oa; // Accessor
```

### Get Sisa Tagihan
```php
$sisa = $kendaraan->oaPayment?->sisa_tagihan ?? $kendaraan->total_oa;
```

### Update Payment Status
```php
$totalBayar = $payment->jumlah_bayar + $newPayment;
$status = $totalBayar >= $payment->jumlah_tagihan ? 'lunas' : 'partial';

$payment->update([
    'jumlah_bayar' => $totalBayar,
    'status' => $status,
]);
```

---

## 🐛 Troubleshooting

### Issue: "Column 'dp_nominal' not found"
**Solution**: Run migration
```bash
php artisan migrate
```

### Issue: DataTables error "Unknown column 'tujuan_nama'"
**Solution**: Clear cache
```bash
php artisan cache:clear
php artisan view:clear
```

### Issue: Multiple OaPayment records per kendaraan
**Solution**: Check `tipe_pembayaran` filter
```php
// Correct
->where('tipe_pembayaran', 'oa')

// Wrong (will get both 'oa' and 'dp_supplier')
->where('po_kendaraan_id', $id)
```

### Issue: DP tidak muncul di form bayar
**Solution**: Check relation
```php
// In PoKendaraan model
public function oaPayment()
{
    return $this->hasOne(OaPayment::class, 'po_kendaraan_id')
        ->where('tipe_pembayaran', 'oa'); // PENTING!
}
```

---

## 📝 Code Snippets

### Create OaPayment (in PurchaseOrderController)
```php
// Calculate total OA from all penerima
$totalTagihanKendaraan = 0;
foreach ($kendaraanData['penerima'] ?? [] as $penerimaData) {
    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
        $totalTagihanKendaraan += $pakanData['jumlah_kg'] * $pakanData['ongkos_oa'];
    }
}

// Create 1 OaPayment per kendaraan
if ($totalTagihanKendaraan > 0) {
    OaPayment::create([
        'po_kendaraan_id' => $kendaraan->id,
        'po_penerima_id' => null,
        'supplier_id' => $kendaraan->supplier_id,
        'tipe_pembayaran' => 'oa',
        'jumlah_tagihan' => $totalTagihanKendaraan,
        'jumlah_bayar' => $kendaraanData['dp_nominal'] ?? 0,
        'status' => 'pending',
    ]);
}
```

### Query in RekapOaController
```php
$query = \App\Models\PoKendaraan::with([
    'po.cv',
    'supplier',
    'penerimas',
    'oaPayment'
])
->whereIn('status', ['selesai', 'batal'])
->whereHas('po', function ($q) use ($activeCvId, $from, $to) {
    if ($activeCvId) $q->where('cv_id', $activeCvId);
    if ($from) $q->whereDate('tanggal_po', '>=', $from);
    if ($to) $q->whereDate('tanggal_po', '<=', $to);
});
```

### DataTables Column (penerima_list)
```php
->addColumn('penerima_list', fn ($q) => $q->penerimas->pluck('nama_penerima')->join(', '))
```

---

## ✅ Checklist

### Before Deploy
- [ ] Migration files exist
- [ ] Run `php artisan migrate`
- [ ] Test create PO dengan DP
- [ ] Test create PO tanpa DP
- [ ] Test bayar cicilan
- [ ] Test filter di Rekap OA
- [ ] Verify 1 record per kendaraan di `oa_payments`
- [ ] Verify `po_penerima_id` is NULL for new records

### After Deploy
- [ ] Monitor error logs
- [ ] Check data consistency
- [ ] Verify user can create PO
- [ ] Verify user can bayar OA
- [ ] Verify reports are correct

---

## 📞 Support

Jika ada masalah:
1. Check error log: `storage/logs/laravel.log`
2. Check database: `SELECT * FROM oa_payments WHERE po_kendaraan_id = ?`
3. Verify migration: `SELECT * FROM migrations WHERE migration LIKE '%dp%'`
4. Rollback if needed: `php artisan migrate:rollback --step=2`

---

## 📚 Related Files

- `FINAL_DP_OA_KENDARAAN_IMPLEMENTATION.md` - Full documentation
- `SUMMARY_DP_OA_CHANGES.md` - Summary of changes
- `app/Http/Controllers/PurchaseOrderController.php` - Create PO logic
- `app/Http/Controllers/RekapOaController.php` - OA payment logic
- `app/Models/PoKendaraan.php` - Kendaraan model
- `app/Models/OaPayment.php` - Payment model
- `resources/views/pages/keuangan/oa/index.blade.php` - List view
- `resources/views/pages/keuangan/oa/bayar.blade.php` - Payment form

---

**Last Updated**: 10 Mei 2026
