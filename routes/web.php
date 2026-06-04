<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpsAssignmentController;
use App\Http\Controllers\GudangLansirController;
use App\Http\Controllers\GudangStokController;
use App\Http\Controllers\IdtrackController;
use App\Http\Controllers\LansirController;
use App\Http\Controllers\LaporanPembayaranController;
use App\Http\Controllers\LaporanPoController;
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
use App\Http\Controllers\TransferPakanController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:dashboard.view'])->name('dashboard');

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

    Route::get('/purchase-order/lansir', [LansirController::class, 'index'])->name('lansir.index')->middleware('can:lansir.view');

    // Laporan
    Route::get('/laporan/po', [LaporanPoController::class, 'index'])->name('laporan.po')->middleware('can:report.po.view');
    Route::get('/laporan/pembayaran', [LaporanPembayaranController::class, 'index'])->name('laporan.pembayaran')->middleware('can:report.payment.view');

    // Keuangan
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/oa', [RekapOaController::class, 'index'])->name('oa.index')->middleware('can:oa.view');
        Route::get('/oa/{id}/bayar', [RekapOaController::class, 'bayar'])->name('oa.bayar')->middleware('can:payment.create');
        Route::post('/oa/{id}/bayar', [RekapOaController::class, 'storeBayar'])->name('oa.store-bayar')->middleware('can:payment.create');
        Route::get('/pembayaran', [PembayaranSupplierController::class, 'index'])->name('pembayaran.index')->middleware('can:payment.view');
        Route::delete('/pembayaran/{id}', [PembayaranSupplierController::class, 'destroy'])->name('pembayaran.destroy')->middleware('can:payment.confirm');

        Route::get('/rugi-laba', [RekapRugiLabaController::class, 'index'])->name('rugi-laba.index')->middleware('can:rugi-laba.view');
        Route::get('/rugi-laba/create', [RekapRugiLabaController::class, 'create'])->name('rugi-laba.create')->middleware('can:rugi-laba.view');
        Route::post('/rugi-laba', [RekapRugiLabaController::class, 'store'])->name('rugi-laba.store')->middleware('can:rugi-laba.view');
        Route::get('/rugi-laba/{id}', [RekapRugiLabaController::class, 'show'])->name('rugi-laba.show')->middleware('can:rugi-laba.view');
        Route::get('/rugi-laba/{id}/edit', [RekapRugiLabaController::class, 'edit'])->name('rugi-laba.edit')->middleware('can:rugi-laba.view');
        Route::get('/rugi-laba/{id}/export', [RekapRugiLabaController::class, 'export'])->name('rugi-laba.export')->middleware('can:rugi-laba.view');
        Route::get('/rugi-laba/{id}/harian', [RekapRugiLabaController::class, 'harian'])->name('rugi-laba.harian')->middleware('can:rugi-laba.view');
        Route::post('/rugi-laba/{id}/harian', [RekapRugiLabaController::class, 'storeHarian'])->name('rugi-laba.harian.store')->middleware('can:rugi-laba.view');
        Route::delete('/rugi-laba/harian/{harianId}', [RekapRugiLabaController::class, 'destroyHarian'])->name('rugi-laba.harian.destroy')->middleware('can:rugi-laba.view');
    });

    Route::resource('/purchase-order', PurchaseOrderController::class)->middleware(['can:po.view']);
    Route::post('/purchase-order/penerima/{penerimaId}/status', [PurchaseOrderController::class, 'penerimaUpdateStatus'])->name('po-penerima.update-status')->middleware('can:po.edit');
    Route::post('/purchase-order/penerima/{penerimaId}/update-tanggal-tiba', [PurchaseOrderController::class, 'penerimaUpdateTanggalTiba'])->name('po-penerima.update-tanggal-tiba')->middleware('can:po.edit');
    Route::post('/purchase-order/kendaraan/{kendaraanId}/status', [PurchaseOrderController::class, 'kendaraanUpdateStatus'])->name('po-kendaraan.update-status')->middleware('can:po.edit');
    Route::get('/purchase-order/penerima/{penerimaId}/lansir', [PurchaseOrderController::class, 'penerimaLansirPage'])->name('po-penerima.lansir-page')->middleware('can:lansir.create');
    Route::post('/purchase-order/penerima/{penerimaId}/lansir', [PurchaseOrderController::class, 'penerimaStoreLansir'])->name('po-penerima.lansir-store')->middleware('can:lansir.create');

    Route::post('/purchase-order/{id}/lock', [PurchaseOrderController::class, 'lock'])->name('purchase-order.lock')->middleware('can:po.edit');
    Route::post('/purchase-order/{id}/unlock', [PurchaseOrderController::class, 'unlock'])->name('purchase-order.unlock')->middleware('can:po.edit');
    Route::get('/purchase-order/tujuan-by-cv/{cvId}', [PurchaseOrderController::class, 'tujuanByCv'])->name('purchase-order.tujuan-by-cv')->middleware('can:po.view');

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
    Route::post('/purchase-order/lansir/{itemId}/selesai', [PurchaseOrderController::class, 'itemSelesai'])->name('po-item.selesai')->middleware('can:lansir.edit');
    Route::get('/purchase-order/lansir/{itemId}', [PurchaseOrderController::class, 'lansirPage'])->name('po-item.lansir-page')->middleware('can:lansir.view');
    Route::post('/purchase-order/lansir/{itemId}', [PurchaseOrderController::class, 'itemLansir'])->name('po-item.lansir')->middleware('can:lansir.create');
    Route::post('/purchase-order/lansir/{itemId}/lansir-selesai', [PurchaseOrderController::class, 'lansirSelesai'])->name('po-item.lansir-selesai')->middleware('can:lansir.edit');
    Route::get('/purchase-order/lansir/{itemId}/lansir-detail', [PurchaseOrderController::class, 'lansirDetail'])->name('po-item.lansir-detail')->middleware('can:lansir.view');

    Route::post('/master/supplier/quick-store', [SupplierController::class, 'quickStore'])->name('supplier.quick-store')->middleware('can:supplier.create');
    Route::post('/master/tujuan/quick-store', [TujuanController::class, 'quickStore'])->name('tujuan.quick-store')->middleware('can:destination.manage');

    Route::resource('/pengaturan/role', RoleController::class)->middleware(['can:role.view']);
    Route::resource('/pengaturan/user', UserController::class)->middleware(['can:user.view']);
    Route::resource('/pengaturan/perusahaan', CvController::class)->middleware(['can:company.view']);
    Route::resource('/master/tujuan', TujuanController::class)->middleware(['can:destination.view']);
    Route::resource('/master/supplier', SupplierController::class)->middleware(['can:supplier.view']);
    Route::resource('/master/penerima', PenerimaController::class)->middleware(['can:penerima.view']);
    Route::get('/master/supplier/ongkos-angkut/get', [SupplierController::class, 'getOngkosAngkut'])->name('supplier.get-ongkos')->middleware('can:supplier.view');
    Route::get('/master/supplier/jenis-kendaraan/get', [SupplierController::class, 'getJenisKendaraan'])->name('supplier.get-jenis-kendaraan')->middleware('can:supplier.view');
    Route::get('/master/penerima/ongkos-angkut/get', [PenerimaController::class, 'getOngkosAngkut'])->name('penerima.get-ongkos')->middleware('can:penerima.view');
    Route::get('/master/penerima/by-tujuan/get', [PenerimaController::class, 'getByTujuan'])->name('penerima.get-by-tujuan')->middleware('can:penerima.view');
    Route::resource('/master/pakan', KodePakanController::class)->middleware(['can:feed_code.view']);

    // Gudang Stok
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/stok', [GudangStokController::class, 'index'])->name('stok.index')->middleware('can:gudang-stok.view');
        Route::get('/stok/saldo', [GudangStokController::class, 'saldo'])->name('stok.saldo')->middleware('can:gudang-stok.view');
        Route::get('/stok/{id}', [GudangStokController::class, 'show'])->name('stok.show')->middleware('can:gudang-stok.view');
        Route::get('/stok/{id}/input-manual', [GudangStokController::class, 'createManualInput'])->name('stok.input-manual')->middleware('can:gudang-stok.view');
        Route::post('/stok/{id}/input-manual', [GudangStokController::class, 'storeManualInput'])->name('stok.store-manual')->middleware('can:gudang-stok.view');
        Route::get('/mutasi', [GudangStokController::class, 'mutasi'])->name('mutasi.index')->middleware('can:gudang-stok.view');
        Route::get('/mutasi/export', [GudangStokController::class, 'mutasiExport'])->name('mutasi.export')->middleware('can:gudang-stok.view');
        Route::get('/mutasi/export-keluar', [GudangStokController::class, 'stokKeluarExport'])->name('mutasi.export-keluar')->middleware('can:gudang-stok.view');
        Route::get('/lansir', [GudangLansirController::class, 'index'])->name('lansir.index')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/export-rekap', [GudangLansirController::class, 'exportRekap'])->name('lansir.export-rekap')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/export-pdf-ptsum-confirm', [GudangLansirController::class, 'exportPdfPtSumConfirm'])->name('lansir.export-pdf-ptsum-confirm')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/export-pdf-ptsum', [GudangLansirController::class, 'exportPdfPtSum'])->name('lansir.export-pdf-ptsum')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/export-pdf-supplier-confirm', [GudangLansirController::class, 'exportPdfSupplierConfirm'])->name('lansir.export-pdf-supplier-confirm')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/export-pdf-supplier', [GudangLansirController::class, 'exportPdfSupplier'])->name('lansir.export-pdf-supplier')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/create', [GudangLansirController::class, 'create'])->name('lansir.create')->middleware('can:gudang-stok.lansir');
        Route::post('/lansir', [GudangLansirController::class, 'store'])->name('lansir.store')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/{id}/edit', [GudangLansirController::class, 'edit'])->name('lansir.edit')->middleware('can:gudang-stok.lansir');
        Route::put('/lansir/{id}', [GudangLansirController::class, 'update'])->name('lansir.update')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/{id}', [GudangLansirController::class, 'show'])->name('lansir.show')->middleware('can:gudang-stok.lansir');
        Route::post('/lansir/penerima/{id}/update-status', [GudangLansirController::class, 'penerimaUpdateStatus'])->name('lansir.penerima.update-status')->middleware('can:gudang-stok.lansir');
        Route::get('/lansir/api/po-penerima/{id}', [GudangLansirController::class, 'getPoPenerimaData'])->name('lansir.api.po-penerima')->middleware('can:gudang-stok.lansir');
    });

    // Rekap PO
    Route::prefix('purchase-order/{id}')->name('rekap-po.')->group(function () {
        Route::get('/rekap-po', [RekapPoController::class, 'show'])->name('show')->middleware('can:po.view');
        Route::get('/rekap-po/export', [RekapPoController::class, 'export'])->name('export')->middleware('can:report.po.export');
    });

    // Transfer Pakan
    Route::prefix('transfer-pakan')->name('transfer-pakan.')->group(function () {
        Route::get('/', [TransferPakanController::class, 'index'])->name('index');
        Route::get('/create', [TransferPakanController::class, 'create'])->name('create');
        Route::post('/', [TransferPakanController::class, 'store'])->name('store');
        Route::get('/export-pdf-ptsum-confirm', [TransferPakanController::class, 'exportPdfPtSumConfirm'])->name('export-ptsum-confirm');
        Route::get('/export-pdf-ptsum', [TransferPakanController::class, 'exportPdfPtSum'])->name('export-ptsum');
        Route::get('/export-rekap', [TransferPakanController::class, 'exportRekap'])->name('export-rekap');
        Route::post('/penerima/{id}/update-status', [TransferPakanController::class, 'penerimaUpdateStatus'])->name('penerima.update-status');
        Route::get('/{id}/edit', [TransferPakanController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TransferPakanController::class, 'update'])->name('update');
        Route::delete('/{id}', [TransferPakanController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [TransferPakanController::class, 'show'])->name('show');
    });

    // Rekap Lansir
    Route::prefix('keuangan/rekap-lansir')->name('rekap-lansir.')->group(function () {
        Route::get('/', [RekapLansirController::class, 'index'])->name('index')->middleware('can:rekap-lansir.view');
        Route::get('/{id}', [RekapLansirController::class, 'show'])->name('show')->middleware('can:rekap-lansir.view');
        Route::post('/{id}/bayar', [RekapLansirController::class, 'bayar'])->name('bayar')->middleware('can:rekap-lansir.bayar');
        Route::get('/{id}/export', [RekapLansirController::class, 'export'])->name('export')->middleware('can:report.payment.export');
    });

    // Idtrack API helpers
    Route::prefix('idtrack')->name('idtrack.')->group(function () {
        Route::get('/markers', [IdtrackController::class, 'markers'])->name('markers')->middleware('can:gps.view');
        Route::post('/kendaraan/{id}/set-spj', [IdtrackController::class, 'setSPJForKendaraan'])->name('set-spj')->middleware('can:gps.manage');
    });

    // GPS Assignment (Idtrack)
    Route::prefix('gps')->name('gps.')->group(function () {
        Route::get('/', [GpsAssignmentController::class, 'trackingMap'])->name('map')->middleware('can:gps.view');
        Route::get('/devices', [GpsAssignmentController::class, 'devices'])->name('devices')->middleware('can:gps.view');
        Route::get('/all-positions', [GpsAssignmentController::class, 'allPositions'])->name('all-positions')->middleware('can:gps.view');
        Route::get('/position-by-nopol', [GpsAssignmentController::class, 'positionByNopol'])->name('position-by-nopol')->middleware('can:gps.view');
        Route::get('/check-geofence', [GpsAssignmentController::class, 'checkGeofence'])->name('check-geofence')->middleware('can:gps.view');
        Route::post('/kendaraan/{id}/assign', [GpsAssignmentController::class, 'assignKendaraan'])->name('kendaraan.assign')->middleware('can:gps.manage');
        Route::delete('/kendaraan/{id}/unassign', [GpsAssignmentController::class, 'unassignKendaraan'])->name('kendaraan.unassign')->middleware('can:gps.manage');
        Route::get('/kendaraan/{id}/position', [GpsAssignmentController::class, 'positionKendaraan'])->name('kendaraan.position')->middleware('can:gps.view');
        Route::get('/kendaraan/{id}/history', [GpsAssignmentController::class, 'historyKendaraan'])->name('kendaraan.history')->middleware('can:gps.view');
        Route::post('/lansir-mobil/{id}/assign', [GpsAssignmentController::class, 'assignLansirMobil'])->name('lansir-mobil.assign')->middleware('can:gps.manage');
        Route::delete('/lansir-mobil/{id}/unassign', [GpsAssignmentController::class, 'unassignLansirMobil'])->name('lansir-mobil.unassign')->middleware('can:gps.manage');
        Route::get('/lansir-mobil/{id}/position', [GpsAssignmentController::class, 'positionLansirMobil'])->name('lansir-mobil.position')->middleware('can:gps.view');
    });

    require __DIR__.'/datatables.php';
});

require __DIR__.'/auth.php';
