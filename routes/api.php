<?php

use App\Http\Controllers\Api\PerangkatController;
use Illuminate\Support\Facades\Route;

// Prefix /api otomatis. Semua route perangkat wajib header X-Device-Token.
// Pengguna memindai QR tong lewat web (/pindai/{kode}); perangkat hanya mengirim foto untuk tongnya.
Route::prefix('perangkat')->middleware('device.token')->group(function () {
    Route::post('foto', [PerangkatController::class, 'foto'])->name('perangkat.foto');
});
