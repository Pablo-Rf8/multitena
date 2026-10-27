<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/assets/app.css', fn () => response()->file(base_path('../frontend/css/app.css'), ['Content-Type' => 'text/css']))->name('assets.css');
Route::get('/assets/app.js', fn () => response()->file(base_path('../frontend/js/app.js'), ['Content-Type' => 'text/javascript']))->name('assets.js');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('super_admin')->group(function () {
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    });
});
