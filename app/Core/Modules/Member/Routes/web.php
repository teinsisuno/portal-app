<?php

use App\Core\Modules\Member\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

// Member module — area member (non-superadmin): profil & pengaturan akun
Route::prefix('member')
    ->middleware(['auth', 'verified', 'member'])
    ->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('member.index');
        Route::put('/profile', [MemberController::class, 'updateProfile'])->name('member.profile.update');
        Route::put('/password', [MemberController::class, 'updatePassword'])->name('member.password.update');
    });
