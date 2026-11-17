<?php

namespace Database\Factories;

use App\Models\LokasiTempatSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** STUB PBI-13 @extends Factory<LokasiTempatSampah> */
class LokasiTempatSampahFactory extends Factory
{
    protected $model = LokasiTempatSampah::class;

    public function definition(): array
    {
        return [
            'nama_lokasi' => 'Titik '.fake()->unique()->word(),
            'kode_titik' => strtoupper(fake()->unique()->bothify('T-##')),
            'area' => fake()->randomElement(['Gedung A', 'Kantin', 'Perpustakaan']),
            'kode_perangkat' => strtoupper(fake()->unique()->bothify('DEV-####')),
            'status' => 'aktif',
        ];
    }
}
