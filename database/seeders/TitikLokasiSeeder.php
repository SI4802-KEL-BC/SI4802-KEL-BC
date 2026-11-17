<?php

namespace Database\Seeders;

use App\Models\KategoriTitik;
use App\Models\TitikLokasi;
use Illuminate\Database\Seeder;

class TitikLokasiSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Organik', 'ORG', '#4d9c1f'],
            ['Plastik', 'PLS', '#0b7dc4'],
            ['Kertas', 'KRT', '#c98a0b'],
        ] as [$nama, $kode, $warna]) {
            KategoriTitik::updateOrCreate(['nama_kategori' => $nama], ['kode' => $kode, 'warna_label' => $warna]);
        }

        // Data contoh mengikuti mockup. Koordinat perkiraan, sesuaikan dengan titik asli.
        foreach ([
            ['A1', 'PRK-A1', 'Kantin TULT - A1', 'Kantin TULT', 'Lantai dasar, sisi barat', -6.974700, 107.630400, 'Tong terpisah berlabel warna', ['Plastik'], 'aktif'],
            ['A2', 'PRK-A2', 'Kantin TULT - A2', 'Kantin TULT', 'Lantai dasar, sisi timur', -6.974600, 107.630500, 'Tong terpisah, area beratap, dekat wastafel', ['Plastik', 'Kertas'], 'aktif'],
            ['B1', 'PRK-B1', 'Gedung Selaru - B1', 'Gedung Selaru', 'Lobi utara', -6.975200, 107.629700, 'Area beratap', ['Kertas'], 'aktif'],
            ['C3', 'PRK-C3', 'Kantin Asrama - C3', 'Kantin Asrama', 'Area cuci piring', -6.976000, 107.631200, 'Dekat wastafel', ['Organik'], 'aktif'],
            ['D1', 'PRK-D1', 'Pendopo - D1', 'Pendopo', 'Sisi selatan pendopo', -6.975500, 107.630800, 'Akses kursi roda', ['Plastik', 'Kertas'], 'aktif'],
            ['E3', 'PRK-E3', 'Parkiran TULT - E3', 'Parkiran TULT', 'Tong rusak, sedang dipindahkan', -6.974200, 107.631000, null, ['Plastik'], 'nonaktif'],
        ] as [$kode, $perangkat, $nama, $area, $ket, $lat, $lng, $fasilitas, $kategori, $status]) {
            $titik = TitikLokasi::firstOrNew(['kode_titik' => $kode]);

            $titik->fill([
                'kode_perangkat' => $perangkat,
                'nama_lokasi' => $nama, 'area' => $area, 'keterangan' => $ket,
                'latitude' => $lat, 'longitude' => $lng, 'fasilitas' => $fasilitas, 'status' => $status,
            ])->save();

            $titik->kategori()->sync(KategoriTitik::whereIn('nama_kategori', $kategori)->pluck('id'));
        }
    }
}
