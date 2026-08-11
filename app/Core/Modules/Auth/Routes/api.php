<?php

use App\Core\Modules\Auth\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Auth module — API /api/v1/auth/* (PRD §11)
Route::prefix('api/v1/auth')->group(function () {
    // Publik
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Butuh token (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    });
});
