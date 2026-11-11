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
            'poin_per_satuan' => 10,
            'batas_per_hari' => null,
            'status' => 'aktif',
        ];
    }
}
