<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach ([
            ['nama' => 'Organik', 'warna' => '#2F855A'],
            ['nama' => 'Plastik', 'warna' => '#3182CE'],
            ['nama' => 'Kertas', 'warna' => '#D69E2E'],
        ] as $category) {
            Category::firstOrCreate(['nama' => $category['nama']], $category);
        }

        foreach (['Gedung Rektorat', 'Gedung Kuliah', 'Asrama Mahasiswa'] as $location) {
            Location::firstOrCreate(['nama' => $location]);
        }
    }
}
