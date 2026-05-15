<?php

use App\Http\Controllers\LaporanPembayaranController;
use App\Http\Controllers\LaporanPoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpsAssignmentController;
use App\Http\Controllers\IdtrackController;
use App\Http\Controllers\GudangLansirController;
use App\Http\Controllers\GudangStokController;
use App\Http\Controllers\LansirController;
use App\Http\Controllers\Master\CvController;
use App\Http\Controllers\Master\KodePakanController;
use App\Http\Controllers\Master\PenerimaController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\TujuanController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\PembayaranSupplierController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RekapLansirController;
use App\Http\Controllers\RekapOaController;
use App\Http\Controllers\RekapPoController;
use App\Http\Controllers\RekapRugiLabaController;
use App\Http\Controllers\RoleController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/switch-cv', [DashboardController::class, 'switchCv'])
    ->middleware(['auth'])->name('switch.cv');

// Idtrack SPJ Callback — tanpa auth (dipanggil oleh server Idtrack)
Route::post('/idtrack/spj-callback', [IdtrackController::class, 'spjCallback'])
    ->name('idtrack.spj-callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/purchase-order/lansir', [LansirController::class, 'index'])->name('lansir.index');

    // Laporan
    Route::get('/laporan/po', [LaporanPoController::class, 'index'])->name('laporan.po');
    Route::get('/laporan/pembayaran', [LaporanPembayaranController::class, 'index'])->name('laporan.pembayaran');

    // Keuangan
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/oa', [RekapOaController::class, 'index'])->name('oa.index');
        Route::get('/oa/{id}/bayar', [RekapOaController::class, 'bayar'])->name('oa.bayar');
        Route::post('/oa/{id}/bayar', [RekapOaController::class, 'storeBayar'])->name('oa.store-bayar');
        Route::get('/pembayaran', [PembayaranSupplierController::class, 'index'])->name('pembayaran.index');

        Route::get('/rugi-laba', [RekapRugiLabaController::class, 'index'])->name('rugi-laba.index');
        Route::get('/rugi-laba/create', [RekapRugiLabaController::class, 'create'])->name('rugi-laba.create');
        Route::post('/rugi-laba', [RekapRugiLabaController::class, 'store'])->name('rugi-laba.store');
        Route::get('/rugi-laba/{id}', [RekapRugiLabaController::class, 'show'])->name('rugi-laba.show');
        Route::get('/rugi-laba/{id}/edit', [RekapRugiLabaController::class, 'edit'])->name('rugi-laba.edit');
        Route::get('/rugi-laba/{id}/export', [RekapRugiLabaController::class, 'export'])->name('rugi-laba.export');
        Route::get('/rugi-laba/{id}/harian', [RekapRugiLabaController::class, 'harian'])->name('rugi-laba.harian');
        Route::post('/rugi-laba/{id}/harian', [RekapRugiLabaController::class, 'storeHarian'])->name('rugi-laba.harian.store');
        Route::delete('/rugi-laba/harian/{harianId}', [RekapRugiLabaController::class, 'destroyHarian'])->name('rugi-laba.harian.destroy');
    });

    Route::resource('/purchase-order', PurchaseOrderController::class);
    Route::post('/purchase-order/penerima/{penerimaId}/status', [PurchaseOrderController::class, 'penerimaUpdateStatus'])->name('po-penerima.update-status');
    Route::post('/purchase-order/kendaraan/{kendaraanId}/status', [PurchaseOrderController::class, 'kendaraanUpdateStatus'])->name('po-kendaraan.update-status');
    Route::get('/purchase-order/penerima/{penerimaId}/lansir', [PurchaseOrderController::class, 'penerimaLansirPage'])->name('po-penerima.lansir-page');
    Route::post('/purchase-order/penerima/{penerimaId}/lansir', [PurchaseOrderController::class, 'penerimaStoreLansir'])->name('po-penerima.lansir-store');

    Route::post('/purchase-order/{id}/lock', [PurchaseOrderController::class, 'lock'])->name('purchase-order.lock');
    Route::post('/purchase-order/{id}/unlock', [PurchaseOrderController::class, 'unlock'])->name('purchase-order.unlock');
    Route::get('/purchase-order/tujuan-by-cv/{cvId}', [PurchaseOrderController::class, 'tujuanByCv'])->name('purchase-order.tujuan-by-cv');

    // Excel exports
    Route::get('/purchase-order-export', [PurchaseOrderController::class, 'export'])->name('purchase-order.export');
    Route::get('/purchase-order/{id}/export-data-awal', [PurchaseOrderController::class, 'exportToPT'])->name('purchase-order.export.data-awal');
    Route::get('/purchase-order/{id}/export', [PurchaseOrderController::class, 'exportPo'])->name('purchase-order.export-po');

    // PDF exports
    Route::get('/purchase-order-export-pdf', [PurchaseOrderController::class, 'exportPdf'])->name('purchase-order.export-pdf');
    Route::get('/purchase-order-export-pdf-supplier', [PurchaseOrderController::class, 'exportPdfSupplier'])->name('purchase-order.export-pdf-supplier');
    Route::get('/purchase-order-export-pdf-supplier-confirm', [PurchaseOrderController::class, 'exportPdfSupplierConfirm'])->name('purchase-order.export-supplier-confirm');
    Route::get('/purchase-order-export-pdf-ptsum', [PurchaseOrderController::class, 'exportPdfPtSum'])->name('purchase-order.export-pdf-ptsum');
    Route::get('/purchase-order-export-pdf-ptsum-confirm', [PurchaseOrderController::class, 'exportPdfPtSumConfirm'])->name('purchase-order.export-ptsum-confirm');
    Route::get('/purchase-order/{id}/export-pdf', [PurchaseOrderController::class, 'exportPoPdf'])->name('purchase-order.export-po-pdf');
    Route::get('/purchase-order/{id}/export-pdf-supplier', [PurchaseOrderController::class, 'exportPoPdfSupplier'])->name('purchase-order.export-po-pdf-supplier');
    Route::get('/purchase-order/{id}/export-pdf-ptsum', [PurchaseOrderController::class, 'exportPoPdfPtSum'])->name('purchase-order.export-po-pdf-ptsum');

    // Arrival actions (per item)
    Route::post('/purchase-order/lansir/{itemId}/selesai', [PurchaseOrderController::class, 'itemSelesai'])->name('po-item.selesai');
    Route::get('/purchase-order/lansir/{itemId}', [PurchaseOrderController::class, 'lansirPage'])->name('po-item.lansir-page');
    Route::post('/purchase-order/lansir/{itemId}', [PurchaseOrderController::class, 'itemLansir'])->name('po-item.lansir');
    Route::post('/purchase-order/lansir/{itemId}/lansir-selesai', [PurchaseOrderController::class, 'lansirSelesai'])->name('po-item.lansir-selesai');
    Route::get('/purchase-order/lansir/{itemId}/lansir-detail', [PurchaseOrderController::class, 'lansirDetail'])->name('po-item.lansir-detail');

    Route::post('/master/supplier/quick-store', [SupplierController::class, 'quickStore'])->name('supplier.quick-store');
    Route::post('/master/tujuan/quick-store', [TujuanController::class, 'quickStore'])->name('tujuan.quick-store');

    Route::resource('/pengaturan/role', RoleController::class);
    Route::resource('/pengaturan/user', UserController::class);
    Route::resource('/pengaturan/perusahaan', CvController::class);
    Route::resource('/master/tujuan', TujuanController::class);
    Route::resource('/master/supplier', SupplierController::class);
    Route::resource('/master/penerima', PenerimaController::class);
    Route::get('/master/supplier/ongkos-angkut/get', [SupplierController::class, 'getOngkosAngkut'])->name('supplier.get-ongkos');
    Route::get('/master/supplier/jenis-kendaraan/get', [SupplierController::class, 'getJenisKendaraan'])->name('supplier.get-jenis-kendaraan');
    Route::get('/master/penerima/ongkos-angkut/get', [PenerimaController::class, 'getOngkosAngkut'])->name('penerima.get-ongkos');
    Route::get('/master/penerima/by-tujuan/get', [PenerimaController::class, 'getByTujuan'])->name('penerima.get-by-tujuan');
    Route::resource('/master/pakan', KodePakanController::class);

    // Gudang Stok
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/stok', [GudangStokController::class, 'index'])->name('stok.index');
        Route::get('/stok/saldo', [GudangStokController::class, 'saldo'])->name('stok.saldo');
        Route::get('/stok/{id}', [GudangStokController::class, 'show'])->name('stok.show');
        Route::get('/mutasi', [GudangStokController::class, 'mutasi'])->name('mutasi.index');
        Route::get('/mutasi/export', [GudangStokController::class, 'mutasiExport'])->name('mutasi.export');
        Route::get('/mutasi/export-keluar', [GudangStokController::class, 'stokKeluarExport'])->name('mutasi.export-keluar');
        Route::get('/lansir', [GudangLansirController::class, 'index'])->name('lansir.index');
        Route::get('/lansir/export-rekap', [GudangLansirController::class, 'exportRekap'])->name('lansir.export-rekap');
        Route::get('/lansir/export-pdf-ptsum-confirm', [GudangLansirController::class, 'exportPdfPtSumConfirm'])->name('lansir.export-pdf-ptsum-confirm');
        Route::get('/lansir/export-pdf-ptsum', [GudangLansirController::class, 'exportPdfPtSum'])->name('lansir.export-pdf-ptsum');
        Route::get('/lansir/export-pdf-supplier-confirm', [GudangLansirController::class, 'exportPdfSupplierConfirm'])->name('lansir.export-pdf-supplier-confirm');
        Route::get('/lansir/export-pdf-supplier', [GudangLansirController::class, 'exportPdfSupplier'])->name('lansir.export-pdf-supplier');
        Route::get('/lansir/create', [GudangLansirController::class, 'create'])->name('lansir.create');
        Route::post('/lansir', [GudangLansirController::class, 'store'])->name('lansir.store');
        Route::get('/lansir/{id}', [GudangLansirController::class, 'show'])->name('lansir.show');
        Route::post('/lansir/penerima/{id}/update-status', [GudangLansirController::class, 'penerimaUpdateStatus'])->name('lansir.penerima.update-status');
        Route::get('/lansir/api/po-penerima/{id}', [GudangLansirController::class, 'getPoPenerimaData'])->name('lansir.api.po-penerima');
    });

    // Rekap PO
    Route::prefix('purchase-order/{id}')->name('rekap-po.')->group(function () {
        Route::get('/rekap-po', [RekapPoController::class, 'show'])->name('show');
        Route::get('/rekap-po/export', [RekapPoController::class, 'export'])->name('export');
    });

    // Rekap Lansir
    Route::prefix('keuangan/rekap-lansir')->name('rekap-lansir.')->group(function () {
        Route::get('/',            [RekapLansirController::class, 'index'])->name('index');
        Route::get('/{id}',        [RekapLansirController::class, 'show'])->name('show');
        Route::post('/{id}/bayar', [RekapLansirController::class, 'bayar'])->name('bayar');
        Route::get('/{id}/export', [RekapLansirController::class, 'export'])->name('export');
    });

    // Idtrack API helpers
    Route::prefix('idtrack')->name('idtrack.')->group(function () {
        Route::get('/markers',                          [IdtrackController::class, 'markers'])->name('markers');
        Route::post('/kendaraan/{id}/set-spj',          [IdtrackController::class, 'setSPJForKendaraan'])->name('set-spj');
    });

    // GPS Assignment (Idtrack)
    Route::prefix('gps')->name('gps.')->group(function () {        Route::get('/',                                           [GpsAssignmentController::class, 'trackingMap'])->name('map');
        Route::get('/devices',                                    [GpsAssignmentController::class, 'devices'])->name('devices');
        Route::get('/all-positions',                              [GpsAssignmentController::class, 'allPositions'])->name('all-positions');
        Route::get('/position-by-nopol',                          [GpsAssignmentController::class, 'positionByNopol'])->name('position-by-nopol');
        Route::get('/check-geofence',                             [GpsAssignmentController::class, 'checkGeofence'])->name('check-geofence');
        Route::post('/kendaraan/{id}/assign',                     [GpsAssignmentController::class, 'assignKendaraan'])->name('kendaraan.assign');
        Route::delete('/kendaraan/{id}/unassign',                 [GpsAssignmentController::class, 'unassignKendaraan'])->name('kendaraan.unassign');
        Route::get('/kendaraan/{id}/position',                    [GpsAssignmentController::class, 'positionKendaraan'])->name('kendaraan.position');
        Route::get('/kendaraan/{id}/history',                     [GpsAssignmentController::class, 'historyKendaraan'])->name('kendaraan.history');
        Route::post('/lansir-mobil/{id}/assign',                  [GpsAssignmentController::class, 'assignLansirMobil'])->name('lansir-mobil.assign');
        Route::delete('/lansir-mobil/{id}/unassign',              [GpsAssignmentController::class, 'unassignLansirMobil'])->name('lansir-mobil.unassign');
        Route::get('/lansir-mobil/{id}/position',                 [GpsAssignmentController::class, 'positionLansirMobil'])->name('lansir-mobil.position');
    });

    require __DIR__.'/datatables.php';
});

require __DIR__.'/auth.php';
