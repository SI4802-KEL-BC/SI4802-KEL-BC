<?php

use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('schedules')->group(function () {
    Route::get('/options', [ScheduleController::class, 'options']);
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/{schedule}', [ScheduleController::class, 'show']);
    Route::put('/{schedule}', [ScheduleController::class, 'update']);
    Route::patch('/{schedule}/status', [ScheduleController::class, 'updateStatus']);
});
