<?php

use App\Core\Modules\Subscription\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Subscription module — langganan tenant
Route::middleware('auth')->group(function () {
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
});
