<?php

use App\Core\Modules\Apps\Controllers\AppsController;
use Illuminate\Support\Facades\Route;

// Apps module — katalog aplikasi
Route::get('/apps', [AppsController::class, 'index'])->middleware('auth')->name('apps.index');
