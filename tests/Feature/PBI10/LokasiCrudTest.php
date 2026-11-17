<?php

namespace Tests\Feature\PBI10;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use Database\Seeders\PilahkuDemoSeeder;
use Database\Seeders\TitikLokasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SCRUM-33 · PBI-10 CRUD Lokasi Tempat Sampah (Admin\LokasiController, view titik/admin)
 *
 * Use Case  : Mengelola Lokasi Tempat Sampah (UC-10)
 * Aktor     : Admin
 * Awal      : Admin sudah login dan membuka menu lokasi tempat sampah
 * Akhir     : Data lokasi tersimpan dan diperbarui
 */
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

    /**
     * TC-10-12 · Titik Nonaktif tidak tampil / tidak bisa dipakai pengguna.
     * Peta pengguna (PBI-11) belum dibuat, jadi yang diuji adalah halaman pengguna yang sudah ada:
     * pilihan lokasi di jadwal publik (PBI-08) dan pindai QR tong (PBI-13).
     */
    public function test_tc_10_12_titik_nonaktif_tidak_tampil_untuk_pengguna(): void
    {
        $titik = $this->titik(['nama_lokasi' => 'Pendopo Nonaktif', 'kode_titik' => 'D1', 'kode_perangkat' => 'PRK-D1']);

        $this->post(route('admin.lokasi.toggle', $titik));
        $this->assertSame('nonaktif', $titik->fresh()->status);

        $pengguna = Pengguna::factory()->create();

        $this->actingAs($pengguna)->get(route('schedules.public'))
            ->assertOk()
            ->assertViewHas('locations', fn ($lokasi) => ! $lokasi->contains('id_lokasi', $titik->id_lokasi))
            ->assertDontSee('Pendopo Nonaktif');

        $this->actingAs($pengguna)->get('/pindai/PRK-D1')
            ->assertRedirect(route('pindai.index'))
            ->assertSessionHasErrors(['kode_perangkat' => 'Tong sampah ini sedang tidak aktif.']);

        // Di peta admin titik tetap ada, ditandai tidak aktif (abu-abu).
        $this->actingAs(Pengguna::factory()->admin()->create())->get(route('admin.lokasi.index'))
            ->assertViewHas('titikPeta', fn ($peta) => $peta->firstWhere('kode', 'D1')['aktif'] === false);
    }

    /** TC-10-13 · Tanpa login → redirect login; user biasa → 403. */
    public function test_tc_10_13_akses_tanpa_login_atau_user_biasa(): void
    {
        auth()->logout();
        $this->get(route('admin.lokasi.index'))->assertRedirect(route('login'));
        $this->post(route('admin.lokasi.store'))->assertRedirect(route('login'));

        $this->actingAs(Pengguna::factory()->create());
        $this->get(route('admin.lokasi.index'))->assertForbidden();
        $this->post(route('admin.lokasi.store'))->assertForbidden();
    }

    public function test_kode_perangkat_kosong_dibuat_otomatis(): void
    {
        $this->post(route('admin.lokasi.store'), $this->dataValid(['kode_perangkat' => '']))
            ->assertRedirect(route('admin.lokasi.index'));

        $this->assertSame(30, strlen(LokasiTempatSampah::where('kode_titik', 'N-01')->value('kode_perangkat')));
    }

    public function test_panel_tambah_dan_edit_menampilkan_form_dan_peta_mini(): void
    {
        $titik = $this->titik();

        $this->get(route('admin.lokasi.index', ['tambah' => 1]))
            ->assertOk()
            ->assertSee(route('admin.lokasi.store'))
            ->assertSee('peta-mini', false);

        $this->get(route('admin.lokasi.index', ['edit' => $titik->id_lokasi]))
            ->assertOk()
            ->assertSee(route('admin.lokasi.update', $titik))
            ->assertSee('peta-mini', false);
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

    public function test_seeder_titik_lokasi_mengisi_data_contoh_mockup(): void
    {
        $this->seed(TitikLokasiSeeder::class);
        $this->seed(TitikLokasiSeeder::class); // aman dijalankan ulang

        $this->assertSame(6, LokasiTempatSampah::count());
        $this->assertSame(0, LokasiTempatSampah::whereNull('latitude')->orWhereNull('longitude')->count());
        $this->assertSame('nonaktif', LokasiTempatSampah::where('kode_titik', 'E3')->value('status'));
        $this->assertEqualsCanonicalizing(
            ['Plastik', 'Kertas'],
            LokasiTempatSampah::where('kode_titik', 'A2')->firstOrFail()->kategori->pluck('nama_kategori')->all(),
        );

        // Semua titik (termasuk nonaktif, berwarna abu-abu) dikirim ke peta admin.
        $this->get(route('admin.lokasi.index'))
            ->assertViewHas('titikPeta', fn ($peta) => $peta->count() === 6);
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

    // ------------------------------------------------------------------ Use Case

    /** Skenario Normal: buka menu → pilih tambah/ubah → isi data → Simpan → daftar diperbarui. */
    public function test_use_case_skenario_normal(): void
    {
        // Membuka menu lokasi → daftar lokasi tampil.
        $this->get(route('admin.lokasi.index'))->assertOk()->assertSee('Kelola Lokasi Tempat Sampah');

        // Memilih tambah lokasi → formulir tampil.
        $this->get(route('admin.lokasi.index', ['tambah' => 1]))->assertOk()->assertSee(route('admin.lokasi.store'));

        // Mengisi nama, koordinat, kategori lalu Simpan → data tersimpan dan daftar diperbarui.
        $this->post(route('admin.lokasi.store'), $this->dataValid([
            'nama_lokasi' => 'Kantin TULT - A2',
            'kode_titik' => 'A2',
            'kategori' => [$this->kategori()->id_kategori, $this->kategori('Kertas', 'KRT')->id_kategori],
        ]))->assertRedirect(route('admin.lokasi.index'))->assertSessionHas('status', 'Lokasi berhasil ditambahkan.');

        $titik = LokasiTempatSampah::where('kode_titik', 'A2')->firstOrFail();
        $this->assertSame(2, $titik->kategori()->count());
        $this->get(route('admin.lokasi.index'))->assertSee('Kantin TULT - A2');

        // Memilih ubah lokasi → perubahan tersimpan, id tetap.
        $this->get(route('admin.lokasi.index', ['edit' => $titik->id_lokasi]))->assertSee(route('admin.lokasi.update', $titik));
        $this->put(route('admin.lokasi.update', $titik), $this->dataValid(['nama_lokasi' => 'Kantin TULT Barat - A2', 'kode_titik' => 'A2']))
            ->assertSessionHas('status', 'Lokasi berhasil diperbarui.');
        $this->assertSame('Kantin TULT Barat - A2', LokasiTempatSampah::find($titik->id_lokasi)->nama_lokasi);
    }

    /** Skenario Gagal: koordinat di luar kawasan Telkom University → ditolak dengan pesan. */
    public function test_use_case_skenario_gagal(): void
    {
        $this->from(route('admin.lokasi.index', ['tambah' => 1]))->followingRedirects()
            ->post(route('admin.lokasi.store'), $this->dataValid(['latitude' => '-7.000000', 'longitude' => '107.700000']))
            ->assertOk()
            ->assertSee('Koordinat berada di luar kawasan Telkom University.');

        $this->assertDatabaseCount('lokasi_tempat_sampah', 0);
    }
}
