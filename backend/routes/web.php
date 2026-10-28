<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchUserController;
use App\Http\Controllers\CompanyAdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/assets/app.css', fn () => response()->file(base_path('../frontend/css/app.css'), ['Content-Type' => 'text/css']))->name('assets.css');
Route::get('/assets/app.js', fn () => response()->file(base_path('../frontend/js/app.js'), ['Content-Type' => 'text/javascript']))->name('assets.js');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.store');
});

Route::middleware(['auth', 'active_account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('tenant_user')->group(function () {
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
        Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::get('/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::post('/contacts/{contact}/notes', [ContactNoteController::class, 'store'])->name('contacts.notes.store');
        Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
        Route::get('/opportunities/export', [OpportunityController::class, 'export'])->name('opportunities.export');
        Route::get('/opportunities/create', [OpportunityController::class, 'create'])->name('opportunities.create');
        Route::post('/opportunities', [OpportunityController::class, 'store'])->name('opportunities.store');
        Route::get('/opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])->name('opportunities.edit');
        Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update'])->name('opportunities.update');
    });

    Route::middleware('company_admin')->group(function () {
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::patch('/branches/{branch}/status', [BranchController::class, 'status'])->name('branches.status');
        Route::get('/branch-users/create', [BranchUserController::class, 'create'])->name('branch-users.create');
        Route::post('/branch-users', [BranchUserController::class, 'store'])->name('branch-users.store');
        Route::get('/branch-users/{user}/edit', [BranchUserController::class, 'edit'])->name('branch-users.edit');
        Route::put('/branch-users/{user}', [BranchUserController::class, 'update'])->name('branch-users.update');
        Route::patch('/branch-users/{user}/status', [BranchUserController::class, 'status'])->name('branch-users.status');
        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    });

    Route::middleware('super_admin')->group(function () {
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::patch('/companies/{company}/status', [CompanyController::class, 'status'])->name('companies.status');
        Route::get('/companies/{company}/admins/create', [CompanyAdminController::class, 'create'])->name('company-admins.create');
        Route::post('/companies/{company}/admins', [CompanyAdminController::class, 'store'])->name('company-admins.store');
        Route::get('/companies/{company}/admins/{user}/edit', [CompanyAdminController::class, 'edit'])->name('company-admins.edit');
        Route::put('/companies/{company}/admins/{user}', [CompanyAdminController::class, 'update'])->name('company-admins.update');
        Route::patch('/companies/{company}/admins/{user}/status', [CompanyAdminController::class, 'status'])->name('company-admins.status');
    });
});
