<?php

namespace App\Providers;

use App\Services\MockVerifikasiOtomatisService;
use App\Services\VerifikasiOtomatisService;
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
        //
    }
}
