<?php

namespace App\Console\Commands;

use App\Models\SesiPembuangan;
use Illuminate\Console\Command;

class SesiKedaluwarsa extends Command
{
    protected $signature = 'sesi:kedaluwarsa';

    protected $description = 'Tandai sesi pembuangan Aktif yang melewati batas waktu sebagai Kedaluwarsa (tanpa membuat aktivitas)';

    public function handle(): int
    {
        $jumlah = SesiPembuangan::where('status_sesi', SesiPembuangan::AKTIF)
            ->where('kedaluwarsa_pada', '<', now())
            ->update(['status_sesi' => SesiPembuangan::KEDALUWARSA]);

        $this->info("{$jumlah} sesi ditandai Kedaluwarsa.");

        return self::SUCCESS;
    }
}
