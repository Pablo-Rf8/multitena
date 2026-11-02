<?php

use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileCrmController;
use App\Http\Controllers\AccessNotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware(['auth:sanctum', 'mobile_session'])->group(function () {
    Route::get('/session', [MobileAuthController::class, 'session']);
    Route::post('/logout', [MobileAuthController::class, 'logout']);
    Route::put('/device/push-token', [MobileAuthController::class, 'registerPush']);
    Route::get('/notifications', [AccessNotificationController::class, 'apiIndex']);
    Route::patch('/notifications/{notification}/read', [AccessNotificationController::class, 'apiRead']);
    Route::get('/contacts', [MobileCrmController::class, 'contacts']);
    Route::post('/contacts', [MobileCrmController::class, 'createContact']);
    Route::get('/opportunities', [MobileCrmController::class, 'opportunities']);
    Route::post('/opportunities', [MobileCrmController::class, 'createOpportunity']);
});
