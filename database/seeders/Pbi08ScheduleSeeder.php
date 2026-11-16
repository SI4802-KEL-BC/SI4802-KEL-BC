<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class Pbi08ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['nama' => 'Organik', 'warna' => '#2F855A', 'kode' => 'organik'],
            ['nama' => 'Plastik', 'warna' => '#3182CE', 'kode' => 'plastik'],
            ['nama' => 'Kertas', 'warna' => '#D69E2E', 'kode' => 'kertas'],
        ])->mapWithKeys(fn (array $category): array => [
            $category['nama'] => Category::firstOrCreate(
                ['nama_kategori' => $category['nama']],
                [...$category, 'status' => 'aktif'],
            ),
        ]);

        $locations = collect([
            'Kantin TULT A1',
            'Kantin TULT A2',
            'Gedung Selaru B1',
            'Kantin Asrama C1',
            'Pendopo D1',
            'Parkiran TULT E1',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Location::firstOrCreate(
                ['nama_lokasi' => $name],
                [
                    'nama' => $name,
                    'kode_titik' => strtoupper(substr(hash('sha256', $name), 0, 10)),
                    'area' => 'Kampus',
                    'kode_perangkat' => 'seed-'.substr(hash('sha256', $name), 0, 20),
                    'status' => 'aktif',
                ],
            ),
        ]);

        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $schedules = [
            [
                'key' => 'PBI08 seed: jadwal aktif hari ini',
                'category' => 'Organik',
                'location' => 'Kantin TULT A1',
                'pola' => 'sekali',
                'start' => $today,
                'end' => null,
                'status' => 'aktif',
                'haul' => '23:59:00',
            ],
            [
                'key' => 'PBI08 seed: jadwal aktif minggu depan',
                'category' => 'Plastik',
                'location' => 'Gedung Selaru B1',
                'pola' => 'sekali',
                'start' => $today->addWeek(),
                'end' => null,
                'status' => 'aktif',
                'haul' => '16:00:00',
            ],
            [
                'key' => 'PBI08 seed: jadwal nonaktif',
                'category' => 'Kertas',
                'location' => 'Kantin Asrama C1',
                'pola' => 'sekali',
                'start' => $today,
                'end' => null,
                'status' => 'nonaktif',
                'haul' => '17:00:00',
            ],
            [
                'key' => 'PBI08 seed: jadwal kedaluwarsa',
                'category' => 'Plastik',
                'location' => 'Pendopo D1',
                'pola' => 'sekali',
                'start' => $today->subDays(14),
                'end' => $today->subDay(),
                'status' => 'aktif',
                'haul' => '18:00:00',
            ],
        ];

        foreach ($schedules as $seed) {
            $schedule = Schedule::updateOrCreate(
                ['catatan' => $seed['key']],
                [
                    'id_kategori' => $categories[$seed['category']]->id_kategori,
                    'pola' => $seed['pola'],
                    'interval_hari' => null,
                    'hari_ke' => null,
                    'hari' => null,
                    'berlaku_mulai' => $seed['start']->toDateString(),
                    'berlaku_sampai' => $seed['end']?->toDateString(),
                    'jam_buang_mulai' => '07:00:00',
                    'jam_buang_selesai' => '15:00:00',
                    'jam_angkut' => $seed['haul'],
                    'kirim_pengingat' => true,
                    'semua_area' => false,
                    'status' => $seed['status'],
                ],
            );
            $schedule->locations()->sync([$locations[$seed['location']]->id_lokasi]);
        }
    }
}
