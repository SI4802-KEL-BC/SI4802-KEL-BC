<?php

namespace Database\Factories;

use App\Models\KategoriSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory untuk test; model KategoriSampah milik SCRUM-27. @extends Factory<KategoriSampah> */
class KategoriSampahFactory extends Factory
{
    protected $model = KategoriSampah::class;

    public function definition(): array
    {
        return [
            'nama_kategori' => fake()->unique()->word(),
            'kode' => strtoupper(fake()->unique()->lexify('???')),
            'warna_label' => '#22c55e',
            'status' => 'aktif',
        ];
    }
}
