<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/monitors');

    Route::resource('monitors', MonitorController::class);
    Route::post('monitors/{monitor}/check', [MonitorController::class, 'check'])->name('monitors.check');
    Route::post('monitors/{monitor}/toggle', [MonitorController::class, 'toggle'])->name('monitors.toggle');
});
