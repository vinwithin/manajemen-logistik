# Testing Guide - Gudang Lansir Nested Structure

## 🧪 Manual Testing Checklist

### Test 1: Create Lansir - Happy Path ✅

**Steps:**
1. Navigate to `/gudang/lansir/create`
2. Select "Gudang Asal" (e.g., Gudang Utama)
3. Fill kendaraan info:
   - No. Polisi: B 1234 XY
   - Nama Sopir: John Doe
   - No. Surat Jalan: SJ-001
   - Tanggal Lansir: Today's date
4. Click "Tambah Penerima"
5. Fill penerima info:
   - Nama Penerima: PT ABC
   - Tujuan: Select any tujuan
6. Click "Tambah Pakan"
7. Select pakan with available stock
8. Enter jumlah kg (less than available stock)
9. Verify karung auto-calculated
10. Click "Tambah Tim" (optional)
11. Fill tim info if added
12. Click "Simpan Lansir"

**Expected Results:**
- ✅ Form submits successfully
- ✅ Redirected to show page
- ✅ Success message displayed
- ✅ All data displayed correctly
- ✅ Stock reduced in gudang_stok
- ✅ Mutation recorded in gudang_mutasi_stok

**Verification Queries:**
```sql
-- Check stock reduction
SELECT * FROM gudang_stok 
WHERE tujuan_id = [gudang_id] 
AND kode_pakan_id = [selected_pakan_id];

-- Check mutation record
SELECT * FROM gudang_mutasi_stok 
WHERE referensi_tipe = 'lansir_gudang_kendaraan' 
ORDER BY created_at DESC LIMIT 1;

-- Check lansir record
SELECT * FROM gudang_lansir_kendaraan 
ORDER BY created_at DESC LIMIT 1;
```

---

### Test 2: Create Lansir - Multiple Penerima ✅

**Steps:**
1. Navigate to `/gudang/lansir/create`
2. Select gudang
3. Fill kendaraan info
4. Add Penerima #1 with 2 pakans
5. Click "Tambah Penerima"
6. Add Penerima #2 with 1 pakan
7. Add tim bongkar to Penerima #1
8. Submit

**Expected Results:**
- ✅ Both penerima saved
- ✅ All pakans saved
- ✅ Tim bongkar saved to correct penerima
- ✅ Total kg/karung calculated correctly
- ✅ Stock reduced for all pakans

---

### Test 3: Stock Validation - Insufficient Stock ⚠️

**Steps:**
1. Navigate to `/gudang/lansir/create`
2. Select gudang
3. Fill kendaraan info
4. Add penerima
5. Select pakan
6. Enter jumlah kg > available stock
7. Try to submit

**Expected Results:**
- ✅ JavaScript shows warning "⚠️ Melebihi stok"
- ✅ Form validation prevents submit
- ✅ Error message: "Ada pakan yang melebihi stok tersedia"
- ✅ No data saved
- ✅ Stock unchanged

---

### Test 4: Dynamic Form - Add/Remove ✅

**Steps:**
1. Navigate to `/gudang/lansir/create`
2. Click "Tambah Penerima" multiple times
3. Verify penerima numbers update correctly
4. Click "Hapus" on middle penerima
5. Verify numbers re-index
6. Add multiple pakans to one penerima
7. Remove some pakans
8. Try to remove last pakan (should fail)
9. Add multiple tims
10. Remove tims

**Expected Results:**
- ✅ Penerima numbers always sequential
- ✅ Can remove penerima (except last one)
- ✅ Can remove pakan (except last one per penerima)
- ✅ Can remove tim freely
- ✅ Form fields properly indexed

---

### Test 5: Auto-Calculate Karung 🧮

**Steps:**
1. Navigate to `/gudang/lansir/create`
2. Add penerima and pakan
3. Enter jumlah kg: 50
4. Verify karung = 1
5. Enter jumlah kg: 75
6. Verify karung = 2
7. Enter jumlah kg: 150
8. Verify karung = 3

**Expected Results:**
- ✅ Karung = ceil(kg / 50)
- ✅ Updates in real-time
- ✅ Read-only field

---

### Test 6: View Show Page 📄

**Steps:**
1. Create a lansir with multiple penerima
2. Navigate to show page
3. Verify all sections display correctly

**Expected Results:**
- ✅ Kendaraan info displayed
- ✅ All penerima listed
- ✅ Pivot table shows pakans correctly
- ✅ Empty cells show "—"
- ✅ Tim bongkar section shows
- ✅ Grand total calculated correctly
- ✅ Creator info displayed
- ✅ Timestamps displayed

---

### Test 7: View Index Page 📋

**Steps:**
1. Navigate to `/gudang/lansir`
2. Verify datatable loads
3. Test filter by gudang
4. Test filter by tanggal
5. Click "Detail" button

**Expected Results:**
- ✅ Datatable loads with data
- ✅ Columns: Tanggal, Gudang, No Polisi, Sopir, Jumlah Penerima, Total KG, Total Karung, Pakan
- ✅ Filters work correctly
- ✅ Detail button redirects to show page
- ✅ Pagination works

---

### Test 8: Mutation Record 📊

**Steps:**
1. Create a lansir
2. Navigate to `/gudang/mutasi`
3. Find the mutation record

**Expected Results:**
- ✅ Mutation type = "keluar"
- ✅ Referensi tipe = "lansir_gudang_kendaraan"
- ✅ Referensi ID = kendaraan ID
- ✅ Jumlah kg/karung correct
- ✅ Saldo after correct
- ✅ Penerima name displayed (if applicable)

---

### Test 9: Stock Display on Create Form 📦

**Steps:**
1. Navigate to `/gudang/lansir/create`
2. Select gudang with stock
3. Add penerima and pakan
4. Click pakan dropdown

**Expected Results:**
- ✅ Only pakans with stock > 0 enabled
- ✅ Pakans with stock = 0 disabled
- ✅ Stock amount shown in dropdown
- ✅ Stock info shown below input field

---

### Test 10: Validation Messages ⚠️

**Steps:**
1. Try to submit empty form
2. Try to submit without gudang
3. Try to submit without penerima
4. Try to submit without pakan

**Expected Results:**
- ✅ Required field validation works
- ✅ Error messages displayed
- ✅ Form highlights invalid fields
- ✅ User-friendly error messages

---

## 🔍 Browser Console Checks

Open browser console (F12) and check for:
- ✅ No JavaScript errors
- ✅ No 404 errors for assets
- ✅ AJAX requests successful
- ✅ No CORS errors

---

## 📊 Database Integrity Checks

```sql
-- Check foreign key constraints
SELECT * FROM gudang_lansir_kendaraan WHERE gudang_id NOT IN (SELECT id FROM tujuan);
SELECT * FROM gudang_lansir_penerima WHERE kendaraan_id NOT IN (SELECT id FROM gudang_lansir_kendaraan);
SELECT * FROM gudang_lansir_pakan WHERE penerima_id NOT IN (SELECT id FROM gudang_lansir_penerima);
SELECT * FROM gudang_lansir_tim WHERE penerima_id NOT IN (SELECT id FROM gudang_lansir_penerima);

-- Check totals match
SELECT 
    k.id,
    k.total_kg,
    k.total_karung,
    SUM(pk.jumlah_kg) as calculated_kg,
    SUM(pk.jumlah_karung) as calculated_karung
FROM gudang_lansir_kendaraan k
LEFT JOIN gudang_lansir_penerima p ON p.kendaraan_id = k.id
LEFT JOIN gudang_lansir_pakan pk ON pk.penerima_id = p.id
GROUP BY k.id
HAVING k.total_kg != calculated_kg OR k.total_karung != calculated_karung;

-- Check stock consistency
SELECT 
    gs.tujuan_id,
    gs.kode_pakan_id,
    gs.stok_kg,
    (
        SELECT SUM(jumlah_kg) 
        FROM gudang_mutasi_stok 
        WHERE tujuan_id = gs.tujuan_id 
        AND kode_pakan_id = gs.kode_pakan_id 
        AND tipe = 'masuk'
    ) - (
        SELECT COALESCE(SUM(jumlah_kg), 0)
        FROM gudang_mutasi_stok 
        WHERE tujuan_id = gs.tujuan_id 
        AND kode_pakan_id = gs.kode_pakan_id 
        AND tipe = 'keluar'
    ) as calculated_stock
FROM gudang_stok gs
HAVING gs.stok_kg != calculated_stock;
```

---

## 🐛 Known Issues / Edge Cases

### Issue 1: Gudang without stock
**Scenario:** User selects gudang with no stock
**Expected:** Form shows message "Tidak ada stok tersedia"
**Status:** ⚠️ Need to implement

### Issue 2: Concurrent stock updates
**Scenario:** Two users create lansir simultaneously
**Expected:** Database locking prevents race condition
**Status:** ✅ Implemented with `lockForUpdate()`

### Issue 3: Large number of penerima
**Scenario:** User adds 10+ penerima
**Expected:** Form remains usable, no performance issues
**Status:** ⚠️ Need to test

---

## ✅ Testing Summary

| Test Case | Status | Notes |
|-----------|--------|-------|
| Create Lansir - Happy Path | ⏳ Pending | Need user testing |
| Multiple Penerima | ⏳ Pending | Need user testing |
| Stock Validation | ⏳ Pending | Need user testing |
| Dynamic Form | ⏳ Pending | Need user testing |
| Auto-Calculate Karung | ⏳ Pending | Need user testing |
| View Show Page | ⏳ Pending | Need user testing |
| View Index Page | ⏳ Pending | Need user testing |
| Mutation Record | ⏳ Pending | Need user testing |
| Stock Display | ⏳ Pending | Need user testing |
| Validation Messages | ⏳ Pending | Need user testing |

---

## 📝 Test Report Template

```
Date: ___________
Tester: ___________
Environment: ___________

Test Results:
[ ] Test 1: Create Lansir - Happy Path
[ ] Test 2: Multiple Penerima
[ ] Test 3: Stock Validation
[ ] Test 4: Dynamic Form
[ ] Test 5: Auto-Calculate Karung
[ ] Test 6: View Show Page
[ ] Test 7: View Index Page
[ ] Test 8: Mutation Record
[ ] Test 9: Stock Display
[ ] Test 10: Validation Messages

Issues Found:
1. ___________
2. ___________
3. ___________

Overall Status: [ ] PASS [ ] FAIL

Notes:
___________
```
