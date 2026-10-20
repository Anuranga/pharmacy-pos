<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GRNController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Pharmacy POS, Inventory & Ledger System
|--------------------------------------------------------------------------
*/

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/switch/{user}', [AuthController::class, 'switchUser'])->name('auth.switch');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('pos.index');
    });

    // POS Counter Terminal (Both Cashier and Admin)
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [POSController::class, 'index'])->name('index');
        Route::get('/search', [POSController::class, 'search'])->name('search');
        Route::get('/barcode', [POSController::class, 'barcodeLookup'])->name('barcode');
        Route::post('/quick-store', [POSController::class, 'quickStoreProduct'])->name('quick-store');
        Route::post('/checkout', [POSController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{sale}', [POSController::class, 'receipt'])->name('receipt');
        Route::post('/void/{sale}', [POSController::class, 'voidSale'])->middleware('role:admin')->name('void');
    });

    // Customer & Lending Ledger (Cashier & Admin)
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::post('/{customer}/settle', [CustomerController::class, 'settle'])->name('settle');
        Route::post('/{customer}/extra-payment', [CustomerController::class, 'extraPayment'])->name('extra-payment');
    });

    // Cashier shift reconciliation & EOD
    Route::get('/reports/eod-z-report', [ReportController::class, 'eodZReport'])->name('reports.eod-z-report');

    // Admin Only Protected Routes
    Route::middleware(['role:admin'])->group(function () {
        // Master Data Management
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::get('/products/create', [MasterDataController::class, 'createProduct'])->name('products.create');
            Route::post('/products', [MasterDataController::class, 'storeProduct'])->name('products.store');
            Route::get('/products/{product}', [MasterDataController::class, 'showProduct'])->name('products.show');
            Route::get('/products/{product}/edit', [MasterDataController::class, 'editProduct'])->name('products.edit');
            Route::put('/products/{product}', [MasterDataController::class, 'updateProduct'])->name('products.update');
            Route::delete('/products/{product}', [MasterDataController::class, 'destroyProduct'])->name('products.destroy');
            Route::get('/categories', [MasterDataController::class, 'categories'])->name('categories');
            Route::post('/categories', [MasterDataController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category}', [MasterDataController::class, 'updateCategory'])->name('categories.update');
            Route::post('/adjust-stock', [MasterDataController::class, 'adjustStock'])->name('adjust-stock');
        });

        // GRN Procurement Pipeline
        Route::prefix('grn')->name('grn.')->group(function () {
            Route::get('/', [GRNController::class, 'index'])->name('index');
            Route::get('/create', [GRNController::class, 'create'])->name('create');
            Route::post('/', [GRNController::class, 'store'])->name('store');
            Route::get('/item-history', [GRNController::class, 'itemHistory'])->name('item-history');
            Route::get('/{grn}', [GRNController::class, 'show'])->name('show');
        });

        // Supplier Payouts & Accounts Payable
        Route::prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('index');
            Route::post('/', [SupplierController::class, 'store'])->name('store');
            Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
            Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
            Route::post('/{supplier}/pay', [SupplierController::class, 'pay'])->name('pay');
        });

        // Management Reports Hub (11 Dedicated Reports)
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/customer-vs-transaction', [ReportController::class, 'customerVsTransaction'])->name('customer-vs-transaction');
            Route::get('/supplier-vs-transaction', [ReportController::class, 'supplierVsTransaction'])->name('supplier-vs-transaction');
            Route::get('/customer-aging', [ReportController::class, 'customerAging'])->name('customer-aging');
            Route::get('/customer-balance-summary', [ReportController::class, 'customerBalanceSummary'])->name('customer-balance-summary');
            Route::get('/sales-by-customer', [ReportController::class, 'salesByCustomer'])->name('sales-by-customer');
            Route::get('/customer-extra-payments', [ReportController::class, 'customerExtraPayments'])->name('customer-extra-payments');
            Route::get('/patient-rx-history', [ReportController::class, 'patientRxHistory'])->name('patient-rx-history');
            Route::get('/inventory-valuation', [ReportController::class, 'inventoryValuation'])->name('inventory-valuation');
            Route::get('/expiry-alerts', [ReportController::class, 'expiryAlerts'])->name('expiry-alerts');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        });
    });
});
