<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('reports.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    Route::get('/register', [SessionController::class, 'createRegister'])->name('register');
    Route::post('/register', [SessionController::class, 'storeRegister'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::resource('doctors', DoctorController::class)->except(['show']);
    Route::resource('reports', ReportController::class);
});
