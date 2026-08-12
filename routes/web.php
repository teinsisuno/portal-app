<?php

use App\Core\Modules\Apps\Models\AppModel;
use Illuminate\Support\Facades\Route;

// Landing page (public — katalog apps tampil untuk guest)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('landing', [
        'apps' => AppModel::orderBy('name')->get(),
    ]);
})->name('home');
