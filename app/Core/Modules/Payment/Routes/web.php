<?php

use App\Core\Modules\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment module — pembayaran tenant
Route::middleware('auth')->group(function () {
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create/{subscription}', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
});
