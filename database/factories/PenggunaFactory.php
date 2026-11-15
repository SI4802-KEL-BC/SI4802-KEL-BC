<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** STUB PBI-13 @extends Factory<Pengguna> */
class PenggunaFactory extends Factory
{
    protected $model = Pengguna::class;

    public function definition(): array
    {
        return [
            'nama_lengkap' => fake()->name(),
            'email_kampus' => fake()->unique()->userName().'@student.kampus.ac.id',
            'kata_sandi' => Hash::make('password'),
            'peran' => 'User',
            'status_akun' => 'aktif',
        ];
    }

    public function admin(): static
    {
        return $this->state(['peran' => 'Admin']);
    }
}
