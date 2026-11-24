<?php

use App\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/monitors');

Route::resource('monitors', MonitorController::class);
Route::post('monitors/{monitor}/check', [MonitorController::class, 'check'])->name('monitors.check');
Route::post('monitors/{monitor}/toggle', [MonitorController::class, 'toggle'])->name('monitors.toggle');
