<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// PBI-13: tandai sesi pembuangan yang melewati batas waktu sebagai Kedaluwarsa.
Schedule::command('sesi:kedaluwarsa')->everyMinute();
