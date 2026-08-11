<?php

use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});
