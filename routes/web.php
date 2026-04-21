<?php

use App\Http\Controllers\GudangLansirController;
use App\Http\Controllers\GudangStokController;
use App\Http\Controllers\LansirController;
use App\Http\Controllers\RekapLansirController;
use App\Http\Controllers\RekapOaController;
use App\Http\Controllers\RekapPoController;
use App\Http\Controllers\PembayaranSupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\KodePakanController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\TujuanController;
use App\Http\Controllers\Master\CvController;
use App\Http\Controllers\Master\TujuanPengirimanController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/', function () {
    return redirect()->route('dashboard');
});
Route::post('/switch-cv', [DashboardController::class, 'switchCv'])
    ->middleware(['auth'])->name('switch.cv');

Route::middleware('auth')->group(function () {
   
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/purchase-order/lansir', [LansirController::class, 'index'])->name('lansir.index');

    // Keuangan
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/oa', [RekapOaController::class, 'index'])->name('oa.index');
        Route::get('/oa/{id}/bayar', [RekapOaController::class, 'bayar'])->name('oa.bayar');
        Route::post('/oa/{id}/bayar', [RekapOaController::class, 'storeBayar'])->name('oa.store-bayar');
        Route::get('/pembayaran', [PembayaranSupplierController::class, 'index'])->name('pembayaran.index');
    });

    Route::resource('/purchase-order', PurchaseOrderController::class);
    Route::post('/purchase-order/penerima/{penerimaId}/status', [PurchaseOrderController::class, 'penerimaUpdateStatus'])->name('po-penerima.update-status');
    Route::post('/purchase-order/kendaraan/{kendaraanId}/status', [PurchaseOrderController::class, 'kendaraanUpdateStatus'])->name('po-kendaraan.update-status');
    Route::get('/purchase-order/penerima/{penerimaId}/lansir', [PurchaseOrderController::class, 'penerimaLansirPage'])->name('po-penerima.lansir-page');
    Route::post('/purchase-order/penerima/{penerimaId}/lansir', [PurchaseOrderController::class, 'penerimaStoreLansir'])->name('po-penerima.lansir-store');

    Route::post('/purchase-order/{id}/lock', [PurchaseOrderController::class, 'lock'])->name('purchase-order.lock');
    Route::post('/purchase-order/{id}/unlock', [PurchaseOrderController::class, 'unlock'])->name('purchase-order.unlock');
    Route::get('/purchase-order/tujuan-by-cv/{cvId}', [PurchaseOrderController::class, 'tujuanByCv'])->name('purchase-order.tujuan-by-cv');
    Route::get('/purchase-order-export', [PurchaseOrderController::class, 'export'])->name('purchase-order.export');
    Route::get('/purchase-order/{id}/export-data-awal', [PurchaseOrderController::class, 'exportToPT'])->name('purchase-order.export.data-awal');
    Route::get('/purchase-order/{id}/export', [PurchaseOrderController::class, 'exportPo'])->name('purchase-order.export-po');
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
    Route::resource('/master/pakan', KodePakanController::class);

    // Gudang Stok
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/stok', [GudangStokController::class, 'index'])->name('stok.index');
        Route::get('/stok/saldo', [GudangStokController::class, 'saldo'])->name('stok.saldo');
        Route::get('/stok/{id}', [GudangStokController::class, 'show'])->name('stok.show');
        Route::get('/mutasi', [GudangStokController::class, 'mutasi'])->name('mutasi.index');
        Route::get('/lansir', [GudangLansirController::class, 'index'])->name('lansir.index');
        Route::get('/lansir/create', [GudangLansirController::class, 'create'])->name('lansir.create');
        Route::post('/lansir', [GudangLansirController::class, 'store'])->name('lansir.store');
        Route::get('/lansir/{id}', [GudangLansirController::class, 'show'])->name('lansir.show');
        Route::post('/lansir/penerima/{id}/update-status', [GudangLansirController::class, 'penerimaUpdateStatus'])->name('lansir.penerima.update-status');
    });

    // Rekap PO
    Route::prefix('purchase-order/{id}')->name('rekap-po.')->group(function () {
        Route::get('/rekap-po', [RekapPoController::class, 'show'])->name('show');
        Route::get('/rekap-po/export', [RekapPoController::class, 'export'])->name('export');
    });

    // Rekap Lansir
    Route::prefix('purchase-order/{id}')->name('rekap-lansir.')->group(function () {
        Route::get('/rekap-lansir',        [RekapLansirController::class, 'show'])->name('show');
        Route::post('/rekap-lansir/bayar', [RekapLansirController::class, 'bayar'])->name('bayar');
        Route::get('/rekap-lansir/export', [RekapLansirController::class, 'export'])->name('export');
    });

    require __DIR__ . '/datatables.php';
});

require __DIR__ . '/auth.php';
