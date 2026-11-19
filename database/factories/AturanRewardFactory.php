<?php

namespace Database\Factories;

use App\Models\AturanReward;
use App\Models\KategoriSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** STUB PBI-13 @extends Factory<AturanReward> */
class AturanRewardFactory extends Factory
{
    protected $model = AturanReward::class;

    public function definition(): array
    {
        return [
            'id_kategori' => KategoriSampah::factory(),
            'kode_aturan' => 'RWD-'.fake()->unique()->numberBetween(10000, 99999),
            'nama_aturan' => fake()->words(3, true),
            'jenis_aturan' => 'Penambahan',
            'poin_per_satuan' => 10,
            'satuan' => 'per item',
            'syarat' => ['aktivitas_terverifikasi'],
            'berlaku_mulai' => now()->toDateString(),
            'batas_per_hari' => null,
            'status' => 'aktif',
        ];
    }
}
