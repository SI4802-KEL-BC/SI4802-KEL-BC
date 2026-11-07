<?php

use App\Http\Controllers\Admin\SchedulePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/jadwal-pengangkutan', [SchedulePageController::class, 'index'])
        ->name('schedules.index');
    Route::post('/jadwal-pengangkutan', [SchedulePageController::class, 'store'])
        ->name('schedules.store');
    Route::put('/jadwal-pengangkutan/{schedule}', [SchedulePageController::class, 'update'])
        ->name('schedules.update');
    Route::patch('/jadwal-pengangkutan/{schedule}/status', [SchedulePageController::class, 'updateStatus'])
        ->name('schedules.status');
});
