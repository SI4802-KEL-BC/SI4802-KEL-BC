<?php

use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\QrIdController;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// STUB PBI-13: placeholder halaman login (milik PBI auth) agar redirect "auth" berfungsi.
Route::get('/login', fn () => response('Halaman login (stub PBI-13)', 200))->name('login');

// STUB PBI-13: login pintas HANYA untuk APP_ENV=local, sementara sampai login PBI-01 masuk.
// Contoh: /dev-login/user@pilahku.test (lihat PilahkuDemoSeeder). Tidak terdaftar di production/testing.
if (app()->environment('local')) {
    Route::get('/dev-login/{email}', function (Request $request, string $email) {
        Auth::login(Pengguna::where('email_kampus', $email)->firstOrFail());
        $request->session()->regenerate();

        return redirect()->route('qr-id.index');
    })->name('dev-login');
}

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
