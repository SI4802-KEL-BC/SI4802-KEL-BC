<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_filter_a_schedule(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();

        $this->postJson('/api/schedules', $this->validSchedule($category, $location))
            ->assertCreated()
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonPath('data.lokasi.0.nama', $location->nama);

        $this->assertDatabaseHas('jadwal_pengangkutan', [
            'id_kategori' => $category->id_kategori,
            'status' => 'aktif',
            'semua_area' => false,
        ]);

        $this->getJson('/api/schedules?status=aktif')
            ->assertOk()
            ->assertJsonPath('meta.aktif', 1)
            ->assertJsonPath('meta.nonaktif', 0);
    }

    public function test_schedule_can_cover_all_areas_without_location_rows(): void
    {
        $this->actingAs($this->admin());
        [$category] = $this->createOptions();
        $schedule = [
            'id_kategori' => $category->id_kategori,
            'semua_area' => true,
            'pola' => 'sekali',
            'berlaku_mulai' => '2026-10-11',
            'jam_buang_mulai' => '07:00',
            'jam_buang_selesai' => '15:00',
            'jam_angkut' => '16:00',
        ];

        $this->postJson('/api/schedules', $schedule)
            ->assertCreated()
            ->assertJsonPath('data.semua_area', true)
            ->assertJsonPath('data.lokasi', []);

        $this->assertDatabaseCount('jadwal_lokasi', 0);
    }

    public function test_schedule_validation_rejects_invalid_times_and_dates(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();
        $schedule = $this->validSchedule($category, $location);
        $schedule['jam_angkut'] = '14:59';
        $schedule['berlaku_sampai'] = '2026-09-30';

        $this->postJson('/api/schedules', $schedule)
            ->assertStatus(400)
            ->assertJsonValidationErrors(['jam_angkut', 'berlaku_sampai']);

        $this->assertDatabaseCount('jadwal_pengangkutan', 0);
    }

    public function test_duplicate_active_schedule_returns_conflict(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();
        $schedule = $this->validSchedule($category, $location);

        $this->postJson('/api/schedules', $schedule)->assertCreated();
        $this->postJson('/api/schedules', $schedule)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Jadwal bentrok dengan jadwal aktif lainnya.');
    }

    public function test_admin_can_update_a_schedule(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();
        $this->postJson('/api/schedules', $this->validSchedule($category, $location))->assertCreated();
        $schedule = Schedule::firstOrFail();
        $updated = $this->validSchedule($category, $location);
        $updated['catatan'] = 'Perubahan jadwal';
        $updated['jam_angkut'] = '17:00';

        $this->putJson("/api/schedules/{$schedule->id_jadwal}", $updated)
            ->assertOk()
            ->assertJsonPath('data.catatan', 'Perubahan jadwal')
            ->assertJsonPath('data.jam_angkut', '17:00');
    }

    public function test_options_only_include_active_categories_and_locations(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();
        Category::create(['nama' => 'Nonaktif', 'warna' => '#777777', 'status' => 'nonaktif']);
        Location::create(['nama' => 'Titik nonaktif', 'status' => 'nonaktif']);

        $this->getJson('/api/schedules/options')
            ->assertOk()
            ->assertJsonCount(1, 'kategori')
            ->assertJsonCount(1, 'lokasi')
            ->assertJsonPath('kategori.0.id_kategori', $category->id_kategori)
            ->assertJsonPath('lokasi.0.id_lokasi', $location->id_lokasi);
    }

    public function test_non_active_location_cannot_be_used_for_a_schedule(): void
    {
        $this->actingAs($this->admin());
        [$category] = $this->createOptions();
        $location = Location::create(['nama' => 'Titik nonaktif', 'status' => 'nonaktif']);

        $this->postJson('/api/schedules', $this->validSchedule($category, $location))
            ->assertStatus(400)
            ->assertJsonValidationErrors(['id_lokasi.0']);

        $this->assertDatabaseCount('jadwal_pengangkutan', 0);
    }

    public function test_admin_can_deactivate_and_reactivate_a_schedule(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();
        $scheduleData = $this->validSchedule($category, $location);
        unset($scheduleData['id_lokasi']);
        $schedule = Schedule::create([
            ...$scheduleData,
            'status' => 'nonaktif',
        ]);
        $schedule->locations()->attach($location->id_lokasi);

        $this->patchJson("/api/schedules/{$schedule->id_jadwal}/status", ['status' => 'aktif'])
            ->assertOk()
            ->assertJsonPath('data.status', 'aktif');

        $this->patchJson("/api/schedules/{$schedule->id_jadwal}/status", ['status' => 'nonaktif'])
            ->assertOk()
            ->assertJsonPath('data.status', 'nonaktif');

        $this->assertDatabaseHas('jadwal_pengangkutan', [
            'id_jadwal' => $schedule->id_jadwal,
            'status' => 'nonaktif',
        ]);
    }

    public function test_reactivating_a_conflicting_schedule_is_rejected(): void
    {
        $this->actingAs($this->admin());
        [$category, $location] = $this->createOptions();
        $this->postJson('/api/schedules', $this->validSchedule($category, $location))->assertCreated();
        $scheduleData = $this->validSchedule($category, $location);
        unset($scheduleData['id_lokasi']);
        $inactive = Schedule::create([...$scheduleData, 'status' => 'nonaktif']);
        $inactive->locations()->attach($location->id_lokasi);

        $this->patchJson("/api/schedules/{$inactive->id_jadwal}/status", ['status' => 'aktif'])
            ->assertStatus(409);

        $this->assertDatabaseHas('jadwal_pengangkutan', [
            'id_jadwal' => $inactive->id_jadwal,
            'status' => 'nonaktif',
        ]);
    }

    public function test_schedule_endpoints_require_admin_access(): void
    {
        $this->getJson('/api/schedules')->assertUnauthorized();

        $this->actingAs(User::factory()->create(['role' => 'pengguna']));
        $this->getJson('/api/schedules')->assertForbidden();
    }

    public function test_admin_can_open_the_schedule_management_page(): void
    {
        $this->actingAs($this->admin());
        $this->createOptions();

        $this->get('/admin/jadwal-pengangkutan')
            ->assertOk()
            ->assertSee('Jadwal Pengangkutan')
            ->assertSee('Tambah Jadwal');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @return array{0: Category, 1: Location}
     */
    private function createOptions(): array
    {
        return [
            Category::create(['nama' => 'Organik', 'warna' => '#2F855A', 'status' => 'aktif']),
            Location::create(['nama' => 'Gedung Rektorat', 'status' => 'aktif']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validSchedule(Category $category, Location $location): array
    {
        return [
            'id_kategori' => $category->id_kategori,
            'id_lokasi' => [$location->id_lokasi],
            'pola' => 'mingguan',
            'hari' => 'senin',
            'berlaku_mulai' => '2026-10-11',
            'berlaku_sampai' => '2026-12-31',
            'jam_buang_mulai' => '07:00',
            'jam_buang_selesai' => '15:00',
            'jam_angkut' => '16:00',
            'catatan' => '',
            'kirim_pengingat' => true,
        ];
    }
}
