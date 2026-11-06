<?php

namespace App\Providers;

use App\Models\AktivitasPemilahan;
use App\Policies\AktivitasPolicy;
use App\Services\MockVerifikasiOtomatisService;
use App\Services\VerifikasiOtomatisService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Model klasifikasi gambar disimulasikan; ganti implementasi bila model nyata tersedia.
        $this->app->bind(VerifikasiOtomatisService::class, MockVerifikasiOtomatisService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama policy mengikuti spec (AktivitasPolicy), bukan konvensi AktivitasPemilahanPolicy.
        Gate::policy(AktivitasPemilahan::class, AktivitasPolicy::class);
    }
}
