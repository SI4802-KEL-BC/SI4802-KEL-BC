<?php

namespace Database\Factories;

use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SesiPembuangan> */
class SesiPembuanganFactory extends Factory
{
    protected $model = SesiPembuangan::class;

    public function definition(): array
    {
        return [
            'id_pengguna' => Pengguna::factory(),
            'id_lokasi' => LokasiTempatSampah::factory(),
            'token_qr' => hash('sha256', Str::random(40)),
            'status_sesi' => SesiPembuangan::AKTIF,
            'waktu_scan' => now(),
            'kedaluwarsa_pada' => now()->addSeconds(config('pilahku.session_ttl')),
        ];
    }

    public function kedaluwarsaLewat(): static
    {
        return $this->state(fn () => [
            'waktu_scan' => now()->subMinutes(10),
            'kedaluwarsa_pada' => now()->subMinutes(5),
        ]);
    }
}
