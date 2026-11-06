<?php

use App\Http\Controllers\KategoriController;

// STUB PBI-13: placeholder halaman login (milik PBI auth) agar redirect "auth" berfungsi.
Route::get('/login', fn () => response('Halaman login (stub PBI-13)', 200))->name('login');

Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
Route::post('/kategori/{kategori}/toggle', [KategoriController::class, 'toggle'])->name('kategori.toggle');
