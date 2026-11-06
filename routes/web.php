<?php

use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\QrIdController;

// STUB PBI-13: placeholder halaman login (milik PBI auth) agar redirect "auth" berfungsi.
Route::get('/login', fn () => response('Halaman login (stub PBI-13)', 200))->name('login');

// PBI-13: halaman pengguna (wajib login).
Route::middleware('auth')->group(function () {
    Route::get('/qr-id', [QrIdController::class, 'index'])->name('qr-id.index');
    Route::post('/qr-id/regenerate', [QrIdController::class, 'regenerate'])->name('qr-id.regenerate');

    Route::get('/aktivitas/{aktivitas}', [AktivitasController::class, 'show'])->name('aktivitas.show');
    Route::patch('/aktivitas/{aktivitas}/catatan', [AktivitasController::class, 'ubahCatatan'])->name('aktivitas.catatan');
    Route::delete('/aktivitas/{aktivitas}', [AktivitasController::class, 'destroy'])->name('aktivitas.destroy');
});

Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
Route::post('/kategori/{kategori}/toggle', [KategoriController::class, 'toggle'])->name('kategori.toggle');
