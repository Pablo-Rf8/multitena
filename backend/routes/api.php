<?php

use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileCrmController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware(['auth:sanctum', 'mobile_session'])->group(function () {
    Route::get('/session', [MobileAuthController::class, 'session']);
    Route::post('/logout', [MobileAuthController::class, 'logout']);
    Route::get('/contacts', [MobileCrmController::class, 'contacts']);
    Route::post('/contacts', [MobileCrmController::class, 'createContact']);
    Route::get('/opportunities', [MobileCrmController::class, 'opportunities']);
    Route::post('/opportunities', [MobileCrmController::class, 'createOpportunity']);
});
