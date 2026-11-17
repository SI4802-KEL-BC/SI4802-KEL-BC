<?php

use App\Http\Controllers\Titik\AdminTitikController;
use Illuminate\Support\Facades\Route;

// PBI-10 (Fathur): berdiri sendiri, belum memakai login. Tambahkan middleware saat PBI-01 masuk.
Route::prefix('titik/admin')->name('titik.admin.')->group(function () {
    Route::get('/', [AdminTitikController::class, 'index'])->name('index');
    Route::post('/', [AdminTitikController::class, 'store'])->name('store');
    Route::put('/{titik}', [AdminTitikController::class, 'update'])->name('update');
    Route::post('/{titik}/toggle', [AdminTitikController::class, 'toggle'])->name('toggle');
});
