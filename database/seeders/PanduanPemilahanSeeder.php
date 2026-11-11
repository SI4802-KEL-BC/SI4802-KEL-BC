<?php

namespace Database\Seeders;

use App\Models\KategoriSampah;
use App\Models\PanduanPemilahan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PanduanPemilahanSeeder extends Seeder
{
    public function run(): void
    {
        KategoriSampah::query()
            ->where('status', 'aktif')
            ->whereNotNull('cara_penanganan')
            ->each(function (KategoriSampah $kategori): void {
                $slug = Str::slug('panduan-'.$kategori->nama_kategori);

                PanduanPemilahan::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'id_kategori' => $kategori->id_kategori,
                        'judul' => 'Panduan memilah '.$kategori->nama_kategori,
                        'ringkasan' => $kategori->deskripsi,
                        'langkah_pemilahan' => $kategori->cara_penanganan,
                        'status' => 'aktif',
                    ],
                );
            });
    }
}
