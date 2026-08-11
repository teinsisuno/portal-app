<?php

use App\Core\Modules\Auth\Controllers\ForgotPasswordController;
use App\Core\Modules\Auth\Controllers\LoginController;
use App\Core\Modules\Auth\Controllers\LogoutController;
use App\Core\Modules\Auth\Controllers\RegisterController;
use App\Core\Modules\Auth\Controllers\ResetPasswordController;
use App\Core\Modules\Auth\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

// Auth-required routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verify-email', [VerificationController::class, 'send'])->name('verification.send');
    Route::get('/verify-email/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
});
