<?php

use App\Core\Modules\Tenant\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Tenant module — dashboard & profil tenant
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
