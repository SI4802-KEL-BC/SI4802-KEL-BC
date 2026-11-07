<?php

use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PanduanPemilahanController;

Route::view('/', 'home')->name('home');
Route::get('/panduan', [PanduanPemilahanController::class, 'index'])->name('panduan.index');
Route::get('/panduan/kategori/{id}', [PanduanPemilahanController::class, 'showKategori'])->name('panduan.kategori');
Route::get('/panduan/{slug}', [PanduanPemilahanController::class, 'show'])->name('panduan.show');

Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
Route::post('/kategori/{kategori}/toggle', [KategoriController::class, 'toggle'])->name('kategori.toggle');
