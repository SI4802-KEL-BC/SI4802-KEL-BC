<?php

use App\Http\Controllers\Admin\QrTongController;
use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PindaiController;

// STUB PBI-13 - diganti saat PBI-01 masuk: halaman login tampilan saja (tanpa POST), agar redirect "auth" berfungsi.
Route::get('/login', fn () => view('auth.login'))->name('login');

// PBI-13: halaman pengguna (wajib login).
Route::middleware('auth')->group(function () {
    // Pengguna memindai QR yang tertempel di tong sampah (URL tetap per tong).
    Route::get('/pindai', [PindaiController::class, 'index'])->name('pindai.index');
    Route::post('/pindai', [PindaiController::class, 'simpan'])->name('pindai.simpan');
    Route::get('/pindai/{kode}', [PindaiController::class, 'mulai'])->where('kode', '[A-Za-z0-9_-]+')->name('pindai.mulai');
        // PBI-14: riwayat aktivitas milik sendiri.
    Route::get('/aktivitas', [AktivitasController::class, 'index'])->name('aktivitas.index');
    Route::get('/aktivitas/{aktivitas}', [AktivitasController::class, 'show'])->name('aktivitas.show');
        // PBI-15: status terbaru (JSON) untuk pembaruan otomatis di halaman detail.
    Route::get('/aktivitas/{aktivitas}/status', [AktivitasController::class, 'status'])->name('aktivitas.status');
    Route::patch('/aktivitas/{aktivitas}/catatan', [AktivitasController::class, 'ubahCatatan'])->name('aktivitas.catatan');
    Route::delete('/aktivitas/{aktivitas}', [AktivitasController::class, 'destroy'])->name('aktivitas.destroy');
});

// PBI-13: QR tong sampah untuk dicetak (hanya Admin).
Route::middleware(['auth', 'role:admin'])->get('/admin/qr-tong', [QrTongController::class, 'index'])->name('admin.qr-tong.index');

// PBI-13: verifikasi manual tahap 2 (hanya Admin).
Route::middleware(['auth', 'role:admin'])->prefix('admin/verifikasi')->name('admin.verifikasi.')->group(function () {
    Route::get('/', [VerifikasiController::class, 'index'])->name('index');
    Route::get('/{aktivitas}', [VerifikasiController::class, 'show'])->name('show');
    Route::post('/{aktivitas}', [VerifikasiController::class, 'proses'])->name('proses');
});

Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
Route::post('/kategori/{kategori}/toggle', [KategoriController::class, 'toggle'])->name('kategori.toggle');
