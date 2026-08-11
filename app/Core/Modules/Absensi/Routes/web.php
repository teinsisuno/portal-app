<?php

use App\Core\Modules\Absensi\Controllers\AppOpenController;
use Illuminate\Support\Facades\Route;

// Absensi module — integrasi Central ↔ produk Absensi (SSO redirect)
Route::middleware('auth')->group(function () {
    Route::get('/apps/{slug}/open', AppOpenController::class)->name('apps.open');
});
