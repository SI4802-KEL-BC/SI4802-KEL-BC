<?php

namespace Database\Factories;

use App\Models\Pengguna;
use App\Models\TransaksiPoin;
use Illuminate\Database\Eloquent\Factories\Factory;

/** STUB PBI-13 @extends Factory<TransaksiPoin> */
class TransaksiPoinFactory extends Factory
{
    protected $model = TransaksiPoin::class;

    public function definition(): array
    {
        return [
            'id_pengguna' => Pengguna::factory(),
            'id_aktivitas' => null,
            'tanggal' => now(),
            'jenis' => 'Perolehan',
            'jumlah_poin' => 10,
            'keterangan' => null,
        ];
    }
}
