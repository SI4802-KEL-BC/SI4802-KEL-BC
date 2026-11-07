<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Database\Seeders\Pbi08ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-07 10:00:00', 'Asia/Jakarta'));
    }

    public function test_public_page_shows_only_active_schedules_in_the_selected_period(): void
    {
        $category = $this->category();
        $location = $this->location('Kantin TULT A1');
        $this->schedule($category, $location, [
            'pola' => 'mingguan',
            'hari' => 'sabtu',
            'berlaku_mulai' => '2026-11-01',
            'jam_angkut' => '16:00:00',
        ]);
        $this->schedule($this->category('Nonaktif'), $location, [
            'pola' => 'mingguan',
            'hari' => 'sabtu',
            'status' => 'nonaktif',
        ]);
        $this->schedule($this->category('Kedaluwarsa'), $location, [
            'pola' => 'mingguan',
            'hari' => 'sabtu',
            'berlaku_mulai' => '2026-10-01',
            'berlaku_sampai' => '2026-11-06',
        ]);

        $this->get('/jadwal-pengangkutan')
            ->assertOk()
            ->assertSee('Jadwal per Area')
            ->assertSee('Organik')
            ->assertSee('Kantin TULT')
            ->assertSee('Terjadwal')
            ->assertDontSee('Nonaktif')
            ->assertDontSee('Kedaluwarsa');
    }

    public function test_location_filter_only_shows_matching_location_and_all_area_schedules(): void
    {
        $category = $this->category();
        $firstLocation = $this->location('Kantin TULT A1');
        $secondLocation = $this->location('Pendopo D1');
        $this->schedule($category, $firstLocation, [
            'pola' => 'sekali',
            'berlaku_mulai' => '2026-11-07',
        ]);
        $this->schedule($category, $secondLocation, [
            'pola' => 'sekali',
            'berlaku_mulai' => '2026-11-07',
        ]);
        Schedule::create([
            ...$this->scheduleAttributes([
                'pola' => 'sekali',
                'berlaku_mulai' => '2026-11-07',
                'semua_area' => true,
            ]),
            'id_kategori' => $category->id_kategori,
        ]);

        $this->get('/jadwal-pengangkutan?tanggal=2026-11-07&lokasi='.$firstLocation->id_lokasi)
            ->assertOk()
            ->assertSee('Kantin TULT')
            ->assertSee('Semua area')
            ->assertDontSee('data-label="Area">Pendopo</td>', false);
    }

    public function test_today_highlight_uses_the_selected_location(): void
    {
        $category = $this->category();
        $selectedLocation = $this->location('Kantin TULT A1');
        $otherLocation = $this->location('Pendopo D1');
        $schedule = $this->schedule($category, $selectedLocation, [
            'pola' => 'sekali',
            'berlaku_mulai' => '2026-11-07',
            'jam_angkut' => '16:00:00',
        ]);
        $schedule->locations()->attach($otherLocation->id_lokasi);

        $this->get('/jadwal-pengangkutan?lokasi='.$selectedLocation->id_lokasi)
            ->assertOk()
            ->assertSee('Pengangkutan mulai 16:00 di Kantin TULT A1.')
            ->assertDontSee('Pengangkutan mulai 16:00 di Kantin TULT A1, Pendopo D1.');
    }

    public function test_schedules_with_inactive_categories_or_locations_are_hidden(): void
    {
        $activeLocation = $this->location('Kantin TULT A1');
        $inactiveLocation = $this->location('Gedung Selaru B1', 'nonaktif');
        $this->schedule($this->category('Kategori nonaktif', 'nonaktif'), $activeLocation);
        $this->schedule($this->category('Lokasi nonaktif'), $inactiveLocation);

        $this->get('/jadwal-pengangkutan')
            ->assertOk()
            ->assertSee('Jadwal belum tersedia')
            ->assertDontSee('Kategori nonaktif')
            ->assertDontSee('Lokasi nonaktif');
    }

    public function test_public_page_matches_cycle_day_and_weekly_day(): void
    {
        $location = $this->location('Gedung Selaru B1');
        $this->schedule($this->category('Siklus'), $location, [
            'pola' => 'siklus',
            'interval_hari' => 3,
            'hari_ke' => 3,
            'berlaku_mulai' => '2026-11-05',
        ]);

        $this->get('/jadwal-pengangkutan?tanggal=2026-11-07')
            ->assertOk()
            ->assertSee('Siklus');

        $this->get('/jadwal-pengangkutan?tanggal=2026-11-06')
            ->assertOk()
            ->assertSee('Jadwal belum tersedia')
            ->assertSee('Jadwal per Area — Jumat, 06 November');
    }

    public function test_page_calculates_haul_status_from_wib_time(): void
    {
        $this->schedule($this->category(), $this->location('Parkiran TULT E1'), [
            'pola' => 'sekali',
            'berlaku_mulai' => '2026-11-07',
            'jam_angkut' => '09:59:00',
        ]);

        $this->get('/jadwal-pengangkutan?tanggal=2026-11-07')
            ->assertOk()
            ->assertSee('Sudah diangkut');
    }

    public function test_page_shows_empty_state_and_filter_error_instead_of_failing(): void
    {
        $this->get('/jadwal-pengangkutan?tanggal=not-a-date')
            ->assertOk()
            ->assertSee('Format tanggal tidak valid')
            ->assertSee('Jadwal belum tersedia')
            ->assertSee('Muat ulang jadwal');

        $this->get('/jadwal-pengangkutan?lokasi=999')
            ->assertOk()
            ->assertSee('Lokasi tidak tersedia')
            ->assertSee('Jadwal belum tersedia');
    }

    public function test_page_shows_reload_message_when_schedule_data_cannot_be_loaded(): void
    {
        Schema::drop('jadwal_lokasi');

        $this->get('/jadwal-pengangkutan')
            ->assertStatus(500)
            ->assertSee('Jadwal belum dapat dimuat')
            ->assertSee('Muat ulang jadwal');
    }

    public function test_page_is_public_and_renders_the_full_week_strip(): void
    {
        $this->get('/jadwal-pengangkutan')
            ->assertOk()
            ->assertSee('Minggu Ini')
            ->assertSee('Min')
            ->assertSee('Sen')
            ->assertSee('Sel')
            ->assertSee('Rab')
            ->assertSee('Kam')
            ->assertSee('Jum')
            ->assertSee('Sab')
            ->assertSee('Atur Pengingat');
    }

    public function test_week_strip_shows_haul_times_for_the_current_week(): void
    {
        $this->schedule($this->category(), $this->location('Kantin TULT A1'), [
            'pola' => 'mingguan',
            'hari' => 'sabtu',
            'berlaku_mulai' => '2026-11-01',
            'jam_angkut' => '16:00:00',
        ]);

        $this->get('/jadwal-pengangkutan?tanggal=2026-11-14')
            ->assertOk()
            ->assertSee('Angkut 16.00')
            ->assertSee('/jadwal-pengangkutan?tanggal=2026-11-01', false)
            ->assertDontSee('/jadwal-pengangkutan?tanggal=2026-11-14', false)
            ->assertSee('Jadwal per Area — Sabtu, 14 November');
    }

    public function test_pbi08_seeder_creates_requested_categories_locations_and_schedule_states(): void
    {
        $this->seed(Pbi08ScheduleSeeder::class);

        $this->assertDatabaseCount('kategori_sampah', 3);
        $this->assertDatabaseCount('lokasi_tempat_sampah', 6);
        $this->assertDatabaseCount('jadwal_pengangkutan', 4);
        $this->assertDatabaseHas('jadwal_pengangkutan', ['status' => 'nonaktif']);
        $this->assertDatabaseHas('jadwal_pengangkutan', [
            'status' => 'aktif',
            'berlaku_mulai' => '2026-11-07',
            'catatan' => 'PBI08 seed: jadwal aktif hari ini',
        ]);
        $this->assertDatabaseHas('jadwal_pengangkutan', [
            'status' => 'aktif',
            'berlaku_mulai' => '2026-11-14',
            'catatan' => 'PBI08 seed: jadwal aktif minggu depan',
        ]);
        $this->assertDatabaseHas('jadwal_pengangkutan', [
            'status' => 'aktif',
            'berlaku_sampai' => '2026-11-06',
            'catatan' => 'PBI08 seed: jadwal kedaluwarsa',
        ]);
    }

    private function category(string $name = 'Organik', string $status = 'aktif'): Category
    {
        return Category::create([
            'nama' => $name,
            'warna' => '#2F855A',
            'status' => $status,
        ]);
    }

    private function location(string $name, string $status = 'aktif'): Location
    {
        return Location::create([
            'nama' => $name,
            'status' => $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function schedule(Category $category, Location $location, array $overrides = []): Schedule
    {
        $schedule = Schedule::create([
            ...$this->scheduleAttributes($overrides),
            'id_kategori' => $category->id_kategori,
        ]);
        $schedule->locations()->attach($location->id_lokasi);

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function scheduleAttributes(array $overrides = []): array
    {
        return array_replace([
            'pola' => 'mingguan',
            'interval_hari' => null,
            'hari_ke' => null,
            'hari' => 'sabtu',
            'berlaku_mulai' => '2026-11-01',
            'berlaku_sampai' => null,
            'jam_buang_mulai' => '07:00:00',
            'jam_buang_selesai' => '15:00:00',
            'jam_angkut' => '16:00:00',
            'catatan' => null,
            'kirim_pengingat' => true,
            'semua_area' => false,
            'status' => 'aktif',
        ], $overrides);
    }
}
