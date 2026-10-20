<?php

use App\Http\Controllers\POSController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Fast sub-150ms JSON Endpoints
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    // POS Async API Endpoints
    Route::get('/pos/search', [POSController::class, 'search']);
    Route::get('/pos/barcode', [POSController::class, 'barcodeLookup']);
    Route::post('/pos/checkout', [POSController::class, 'checkout']);
    Route::post('/pos/quick-store', [POSController::class, 'quickStoreProduct']);
});
