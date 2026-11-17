<?php

namespace Tests\Feature\PBI10;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use Database\Seeders\PilahkuDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PBI-10 · CRUD lokasi tempat sampah (Admin\LokasiController, view titik/admin). */
class LokasiCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Kelola lokasi hanya untuk Admin.
        $this->actingAs(Pengguna::factory()->admin()->create());
    }

    private function kategori(string $nama = 'Plastik', string $kode = 'PLS'): KategoriSampah
    {
        return KategoriSampah::firstOrCreate(
            ['nama_kategori' => $nama],
            ['kode' => $kode, 'warna_label' => '#0b7dc4', 'status' => 'aktif'],
        );
    }

    private function titik(array $override = []): LokasiTempatSampah
    {
        $kodeTitik = $override['kode_titik'] ?? 'U-01';

        return LokasiTempatSampah::create($override + [
            'nama_lokasi' => 'Kantin Uji',
            'kode_titik' => $kodeTitik,
            'area' => 'Kantin',
            // kode_perangkat wajib unik di tabel lokasi_tempat_sampah.
            'kode_perangkat' => 'PRK-'.$kodeTitik,
            'latitude' => -6.975,
            'longitude' => 107.63,
            'status' => 'aktif',
        ]);
    }

    private function dataValid(array $override = []): array
    {
        return $override + [
            'nama_lokasi' => 'Lokasi Baru',
            'kode_titik' => 'N-01',
            'area' => 'Kantin',
            'kode_perangkat' => 'PRK-N01',
            'latitude' => '-6.975000',
            'longitude' => '107.630000',
            'kategori' => [$this->kategori()->id_kategori],
            'aktif' => '1',
        ];
    }

    // TC-10-01
    public function test_halaman_daftar_menampilkan_titik_dan_peta(): void
    {
        $this->titik(['nama_lokasi' => 'Kantin Satu', 'kode_titik' => 'K-1']);
        $this->titik(['nama_lokasi' => 'Pendopo Dua', 'kode_titik' => 'P-2']);

        $this->get(route('admin.lokasi.index'))
            ->assertOk()
            ->assertSee('Kantin Satu')
            ->assertSee('Pendopo Dua')
            ->assertSee('peta-daftar', false);
    }

    public function test_lokasi_dari_seeder_demo_punya_koordinat_dan_tampil_di_peta(): void
    {
        $this->seed(PilahkuDemoSeeder::class);

        $this->assertSame(0, LokasiTempatSampah::whereNull('latitude')->orWhereNull('longitude')->count());

        $this->get(route('admin.lokasi.index'))
            ->assertOk()
            ->assertViewHas('titikPeta', fn ($peta) => $peta->pluck('kode')->sort()->values()->all() === ['T-01', 'T-02', 'T-03']);
    }

    // TC-10-07
    public function test_daftar_bisa_dicari_dan_difilter(): void
    {
        $this->titik(['nama_lokasi' => 'Kantin Satu', 'kode_titik' => 'K-1']);
        $this->titik(['nama_lokasi' => 'Pendopo Dua', 'kode_titik' => 'P-2', 'area' => 'Pendopo', 'status' => 'nonaktif']);

        $this->get(route('admin.lokasi.index', ['q' => 'kantin']))
            ->assertViewHas('daftar', fn ($d) => $d->pluck('nama_lokasi')->all() === ['Kantin Satu']);

        $this->get(route('admin.lokasi.index', ['status' => 'nonaktif']))
            ->assertViewHas('daftar', fn ($d) => $d->pluck('nama_lokasi')->all() === ['Pendopo Dua']);

        $this->get(route('admin.lokasi.index', ['area' => 'Pendopo']))
            ->assertViewHas('daftar', fn ($d) => $d->pluck('nama_lokasi')->all() === ['Pendopo Dua']);
    }

    // TC-10-02 dan TC-10-03
    public function test_admin_bisa_menambah_lokasi_dengan_koordinat_dan_kategori(): void
    {
        $this->post(route('admin.lokasi.store'), $this->dataValid([
            'latitude' => '-6.974600',
            'longitude' => '107.630500',
        ]))->assertRedirect(route('admin.lokasi.index'));

        $titik = LokasiTempatSampah::where('kode_titik', 'N-01')->firstOrFail();
        $this->assertSame('aktif', $titik->status);
        $this->assertSame('PRK-N01', $titik->kode_perangkat);
        $this->assertEqualsWithDelta(-6.9746, $titik->latitude, 0.00001);
        $this->assertEqualsWithDelta(107.6305, $titik->longitude, 0.00001);
        $this->assertSame(1, $titik->kategori()->count());
    }

    public function test_tanpa_centang_aktif_lokasi_tersimpan_nonaktif(): void
    {
        // Form mengirim aktif=0 (input hidden) bila kotak "Lokasi aktif" tidak dicentang.
        $this->post(route('admin.lokasi.store'), $this->dataValid(['aktif' => '0']));

        $this->assertDatabaseHas('lokasi_tempat_sampah', ['kode_titik' => 'N-01', 'status' => 'nonaktif']);
    }

    // TC-10-04
    public function test_admin_bisa_mengubah_lokasi(): void
    {
        $titik = $this->titik(['kode_perangkat' => 'PRK-LAMA']);

        $this->put(route('admin.lokasi.update', $titik), $this->dataValid([
            'nama_lokasi' => 'Kantin Diubah',
            'kode_titik' => 'U-01',
            'kode_perangkat' => 'PRK-BARU',
            'latitude' => '-6.976000',
        ]))->assertRedirect(route('admin.lokasi.index'));

        $titik->refresh();
        $this->assertSame('Kantin Diubah', $titik->nama_lokasi);
        $this->assertSame('PRK-BARU', $titik->kode_perangkat);
        $this->assertEqualsWithDelta(-6.976, $titik->latitude, 0.00001);
    }

    // TC-10-05 dan TC-10-06
    public function test_admin_bisa_menonaktifkan_dan_mengaktifkan_lagi(): void
    {
        $titik = $this->titik();

        $this->post(route('admin.lokasi.toggle', $titik))->assertRedirect();
        $this->assertSame('nonaktif', $titik->fresh()->status);

        $this->post(route('admin.lokasi.toggle', $titik))->assertRedirect();
        $this->assertSame('aktif', $titik->fresh()->status);
    }

    public function test_lokasi_tidak_bisa_dihapus_permanen(): void
    {
        $titik = $this->titik();

        $this->delete('/admin/lokasi/'.$titik->id_lokasi)->assertStatus(405);
        $this->assertDatabaseHas('lokasi_tempat_sampah', ['id_lokasi' => $titik->id_lokasi]);
    }

    // TC-10-08
    public function test_koordinat_di_luar_kawasan_kampus_ditolak(): void
    {
        $this->post(route('admin.lokasi.store'), $this->dataValid(['latitude' => '-7.000000']))
            ->assertSessionHasErrors(['latitude' => 'Koordinat berada di luar kawasan Telkom University.']);

        $this->assertDatabaseMissing('lokasi_tempat_sampah', ['kode_titik' => 'N-01']);
    }

    // TC-10-09
    public function test_data_wajib_kosong_ditolak(): void
    {
        $this->post(route('admin.lokasi.store'), $this->dataValid(['nama_lokasi' => '', 'kategori' => []]))
            ->assertSessionHasErrors(['nama_lokasi', 'kategori']);

        $this->assertDatabaseCount('lokasi_tempat_sampah', 0);
    }

    // TC-10-10
    public function test_kode_titik_dan_kode_perangkat_duplikat_ditolak(): void
    {
        $this->titik(['kode_titik' => 'N-01', 'kode_perangkat' => 'PRK-N01']);

        $this->post(route('admin.lokasi.store'), $this->dataValid(['kode_perangkat' => 'PRK-LAIN']))
            ->assertSessionHasErrors(['kode_titik' => 'Kode titik sudah digunakan.']);

        $this->post(route('admin.lokasi.store'), $this->dataValid(['kode_titik' => 'N-02']))
            ->assertSessionHasErrors(['kode_perangkat' => 'Kode perangkat sudah digunakan.']);
    }

    // TC-10-11
    public function test_koordinat_bukan_angka_valid_ditolak(): void
    {
        $this->post(route('admin.lokasi.store'), $this->dataValid(['latitude' => 'abc']))
            ->assertSessionHasErrors(['latitude' => 'Format koordinat tidak valid.']);

        $this->post(route('admin.lokasi.store'), $this->dataValid(['latitude' => '200']))
            ->assertSessionHasErrors(['latitude' => 'Format koordinat tidak valid.']);
    }
}
