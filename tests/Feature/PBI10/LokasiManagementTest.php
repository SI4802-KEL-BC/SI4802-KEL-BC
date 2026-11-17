<?php

namespace Tests\Feature\PBI10;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_location_management(): void
    {
        $this->get(route('admin.lokasi.index'))->assertRedirect(route('login'));
        $this->post(route('admin.lokasi.store'))->assertRedirect(route('login'));

        $user = Pengguna::factory()->create(['peran' => 'User']);
        $this->actingAs($user)->get(route('admin.lokasi.index'))->assertForbidden();
        $this->post(route('admin.lokasi.store'))->assertForbidden();
    }

    public function test_admin_can_create_update_and_toggle_a_location(): void
    {
        $this->actingAs(Pengguna::factory()->create(['peran' => 'Admin']));
        $kategori = $this->createCategory();

        $this->post(route('admin.lokasi.store'), [
            'nama_lokasi' => 'Kantin Utama',
            'kode_titik' => 'T-09',
            'area' => 'Kantin',
            'latitude' => '-6.975000',
            'longitude' => '107.630000',
            'kategori' => [$kategori->id_kategori],
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Lokasi berhasil ditambahkan.');

        $lokasi = LokasiTempatSampah::where('kode_titik', 'T-09')->firstOrFail();
        $this->assertSame(30, strlen($lokasi->kode_perangkat));
        $this->assertSame($kategori->id_kategori, $lokasi->kategoriDiterima()->firstOrFail()->id_kategori);

        $this->put(route('admin.lokasi.update', $lokasi), [
            'nama_lokasi' => 'Kantin Baru',
            'kode_titik' => 'T-09',
            'area' => 'Kantin Barat',
            'latitude' => '-6.976000',
            'longitude' => '107.631000',
            'kategori' => [$kategori->id_kategori],
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Lokasi berhasil diperbarui.');

        $this->post(route('admin.lokasi.toggle', $lokasi))
            ->assertRedirect()
            ->assertSessionHas('status', 'Status lokasi berhasil diperbarui.');

        $this->assertDatabaseHas('lokasi_tempat_sampah', [
            'id_lokasi' => $lokasi->id_lokasi,
            'nama_lokasi' => 'Kantin Baru',
            'area' => 'Kantin Barat',
            'status' => 'nonaktif',
        ]);
    }

    public function test_location_management_rejects_coordinates_outside_the_campus_bounds(): void
    {
        $this->actingAs(Pengguna::factory()->create(['peran' => 'Admin']));
        $kategori = $this->createCategory();

        $this->from(route('admin.lokasi.index'))
            ->post(route('admin.lokasi.store'), [
                'nama_lokasi' => 'Lokasi di luar kampus',
                'kode_titik' => 'T-10',
                'area' => 'Luar',
                'latitude' => '-7.000000',
                'longitude' => '107.630000',
                'kategori' => [$kategori->id_kategori],
            ])
            ->assertRedirect(route('admin.lokasi.index'))
            ->assertSessionHasErrors(['latitude']);

        $this->assertDatabaseMissing('lokasi_tempat_sampah', ['kode_titik' => 'T-10']);
    }

    public function test_location_management_page_shows_existing_locations_and_categories(): void
    {
        $this->actingAs(Pengguna::factory()->create(['peran' => 'Admin']));
        $kategori = $this->createCategory();
        $lokasi = LokasiTempatSampah::factory()->create(['nama_lokasi' => 'Gedung Utama']);
        $lokasi->kategoriDiterima()->attach($kategori);

        $this->get(route('admin.lokasi.index'))
            ->assertOk()
            ->assertSee('Gedung Utama')
            ->assertSee('Plastik')
            ->assertSee('peta-daftar', false)
            ->assertSee(route('admin.lokasi.update', $lokasi));

        $this->get(route('admin.lokasi.index', ['edit' => $lokasi->id_lokasi]))
            ->assertOk()
            ->assertSee(route('admin.lokasi.update', $lokasi))
            ->assertSee('peta-mini', false);

        $this->get(route('admin.lokasi.index', ['tambah' => 1]))
            ->assertOk()
            ->assertSee(route('admin.lokasi.store'))
            ->assertSee('peta-mini', false);
    }

    public function test_location_list_can_be_searched_and_filtered(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create());

        $kantinAktif = LokasiTempatSampah::factory()->create([
            'nama_lokasi' => 'Kantin Selatan',
            'kode_titik' => 'K-01',
            'area' => 'Kantin',
            'status' => 'aktif',
        ]);
        $kantinNonaktif = LokasiTempatSampah::factory()->create([
            'nama_lokasi' => 'Kantin Utara',
            'kode_titik' => 'K-02',
            'area' => 'Kantin',
            'status' => 'nonaktif',
        ]);
        $gedung = LokasiTempatSampah::factory()->create([
            'nama_lokasi' => 'Gedung Barat',
            'kode_titik' => 'G-01',
            'area' => 'Gedung',
        ]);

        $this->get(route('admin.lokasi.index', ['q' => 'K-02']))
            ->assertViewHas('daftar', fn ($lokasis) => $lokasis->modelKeys() === [$kantinNonaktif->id_lokasi]);

        $this->get(route('admin.lokasi.index', ['area' => 'Kantin', 'status' => 'aktif']))
            ->assertViewHas('daftar', fn ($lokasis) => $lokasis->modelKeys() === [$kantinAktif->id_lokasi]);

        $this->get(route('admin.lokasi.index', ['area' => 'Gedung']))
            ->assertViewHas('daftar', fn ($lokasis) => $lokasis->modelKeys() === [$gedung->id_lokasi]);
    }

    public function test_admin_can_save_location_details_and_mark_location_inactive(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create());
        $kategori = $this->createCategory();

        $this->post(route('admin.lokasi.store'), [
            'nama_lokasi' => 'Kantin Tambahan',
            'kode_titik' => 'T-11',
            'kode_perangkat' => 'PRK-T11',
            'area' => 'Kantin',
            'keterangan' => 'Sisi barat',
            'fasilitas' => 'Area beratap',
            'latitude' => '-6.975000',
            'longitude' => '107.630000',
            'kategori' => [$kategori->id_kategori],
            'aktif' => '0',
        ])
            ->assertRedirect(route('admin.lokasi.index'))
            ->assertSessionHas('status', 'Lokasi berhasil ditambahkan.');

        $this->assertDatabaseHas('lokasi_tempat_sampah', [
            'kode_titik' => 'T-11',
            'kode_perangkat' => 'PRK-T11',
            'keterangan' => 'Sisi barat',
            'fasilitas' => 'Area beratap',
            'status' => 'nonaktif',
        ]);
    }

    private function createCategory(): KategoriSampah
    {
        return KategoriSampah::create([
            'nama_kategori' => 'Plastik',
            'kode' => 'PLS',
            'status' => 'aktif',
        ]);
    }
}
