<?php

use App\Core\Modules\Admin\Controllers\AppAdminController;
use App\Core\Modules\Admin\Controllers\PaymentAdminController;
use App\Core\Modules\Admin\Controllers\TenantAdminController;
use App\Core\Modules\Admin\Controllers\UserAdminController;
use Illuminate\Support\Facades\Route;

// Admin module — panel pusat, hanya superadmin (FR-006)
Route::prefix('admin')
    ->middleware(['auth', 'superadmin'])
    ->group(function () {
        Route::get('/tenants', [TenantAdminController::class, 'index'])->name('admin.tenants.index');
        Route::get('/tenants/{tenant}', [TenantAdminController::class, 'show'])->name('admin.tenants.show');

        Route::get('/payments', [PaymentAdminController::class, 'index'])->name('admin.payments.index');
        Route::post('/payments/{payment}/confirm', [PaymentAdminController::class, 'confirm'])->name('admin.payments.confirm');
        Route::post('/payments/{payment}/reject', [PaymentAdminController::class, 'reject'])->name('admin.payments.reject');

        Route::get('/apps', [AppAdminController::class, 'index'])->name('admin.apps.index');
        Route::get('/apps/create', [AppAdminController::class, 'create'])->name('admin.apps.create');
        Route::post('/apps', [AppAdminController::class, 'store'])->name('admin.apps.store');
        Route::get('/apps/{app}/edit', [AppAdminController::class, 'edit'])->name('admin.apps.edit');
        Route::put('/apps/{app}', [AppAdminController::class, 'update'])->name('admin.apps.update');

        Route::get('/users', [UserAdminController::class, 'index'])->name('admin.users.index');
    });
