<?php

use App\Http\Controllers\Auth\SsoAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityDashboardController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Tanpa Auth - Dashboard bisa dilihat semua orang)
|--------------------------------------------------------------------------
*/

// Dashboard - Public access
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Detail per Activity - Public access
Route::get('/kegiatan/{slug}', [ActivityDashboardController::class, 'show'])->name('activities.show');

/*
|--------------------------------------------------------------------------
| SSO Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->name('sso.')->group(function () {
    Route::get('login', [SsoAuthController::class, 'redirectToSso'])->name('login');
    Route::get('callback', [SsoAuthController::class, 'handleCallback'])->name('callback');
    Route::post('logout', [SsoAuthController::class, 'logout'])->name('logout');
});

// Redirect /login ke SSO
Route::get('/login', fn() => redirect()->route('sso.login'))->name('login');

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication - untuk upload data)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Activity Management - Admin only
    Route::resource('kegiatan', ActivityController::class)->except(['show'])->parameters([
        'kegiatan' => 'activity'
    ])->names([
        'index' => 'activities.index',
        'create' => 'activities.create',
        'store' => 'activities.store',
        'edit' => 'activities.edit',
        'update' => 'activities.update',
        'destroy' => 'activities.destroy',
    ]);

    // Upload Data per Activity
    Route::get('kegiatan/{activity}/upload', [UploadController::class, 'show'])->name('upload.show');
    Route::post('kegiatan/{activity}/upload/json', [UploadController::class, 'uploadJson'])->name('upload.json');
    Route::post('kegiatan/{activity}/upload/csv', [UploadController::class, 'uploadCsv'])->name('upload.csv');
    Route::post('kegiatan/{activity}/upload/zip', [UploadController::class, 'uploadZip'])->name('upload.zip');

    // Edit PJ Mapping
    Route::post('kegiatan/{activity}/pj-mapping/{pjMapping}', [ActivityController::class, 'updatePjMapping'])->name('pj-mapping.update');
});
