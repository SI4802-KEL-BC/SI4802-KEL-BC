<?php

use App\Http\Controllers\Api\PerangkatController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\Route;

// Prefix /api otomatis. Semua route perangkat wajib header X-Device-Token.
// Pengguna memindai QR tong lewat web (/pindai/{kode}); perangkat hanya mengirim foto untuk tongnya.
Route::prefix('perangkat')->middleware('device.token')->group(function () {
    Route::post('foto', [PerangkatController::class, 'foto'])->name('perangkat.foto');
});

Route::middleware(['auth', 'admin'])->prefix('schedules')->group(function () {
    Route::get('/options', [ScheduleController::class, 'options']);
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/{schedule}', [ScheduleController::class, 'show']);
    Route::put('/{schedule}', [ScheduleController::class, 'update']);
    Route::patch('/{schedule}/status', [ScheduleController::class, 'updateStatus']);
});
