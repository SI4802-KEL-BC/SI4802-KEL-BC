<?php

use Illuminate\Support\Facades\Route;

// Prefix /api otomatis. Route perangkat tempat sampah (PBI-13) ditambahkan di tahap berikutnya.
Route::prefix('perangkat')->middleware('device.token')->group(function () {
    //
});
