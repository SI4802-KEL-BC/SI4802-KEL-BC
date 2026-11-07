<?php

use App\Http\Controllers\PenggunaController;
use Illuminate\Support\Facades\Route;

// Halaman utama
Route::get('/', [PenggunaController::class, 'show']);

// Halaman detail profil
Route::get('/profile', [PenggunaController::class, 'show'])
    ->name('profil.show');

// Halaman edit profil
Route::get('/profile/edit', [PenggunaController::class, 'edit'])
    ->name('profil.edit');

// Proses update profil
Route::put('/profile', [PenggunaController::class, 'update'])
    ->name('profile.update');