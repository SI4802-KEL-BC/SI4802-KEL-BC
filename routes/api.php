<?php

use App\Http\Controllers\Api\PerangkatController;
use Illuminate\Support\Facades\Route;

// Prefix /api otomatis. Semua route perangkat wajib header X-Device-Token.
Route::prefix('perangkat')->middleware('device.token')->group(function () {
    Route::post('scan', [PerangkatController::class, 'scan'])->name('perangkat.scan');
    Route::post('foto', [PerangkatController::class, 'foto'])->name('perangkat.foto');
});
