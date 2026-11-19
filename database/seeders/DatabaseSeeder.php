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
            ['nama' => 'Organik', 'warna' => '#2F855A', 'kode' => 'organik'],
            ['nama' => 'Plastik', 'warna' => '#3182CE', 'kode' => 'plastik'],
            ['nama' => 'Kertas', 'warna' => '#D69E2E', 'kode' => 'kertas'],
        ] as $category) {
            Category::firstOrCreate(['nama_kategori' => $category['nama']], $category);
        }

        foreach (['Gedung Rektorat', 'Gedung Kuliah', 'Asrama Mahasiswa'] as $location) {
            $hash = substr(hash('sha256', $location), 0, 20);
            Location::firstOrCreate(
                ['nama_lokasi' => $location],
                [
                    'nama' => $location,
                    'kode_titik' => strtoupper(substr($hash, 0, 10)),
                    'area' => 'Kampus',
                    'kode_perangkat' => 'seed-'.$hash,
                ],
            );
        }

        $this->call(PanduanPemilahanSeeder::class);
    }
}
