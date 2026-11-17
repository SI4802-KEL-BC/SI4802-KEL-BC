<?php

namespace Tests\Feature\PBI07;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SCRUM-30 · PBI-7 CRUD Jadwal Pengangkutan Sampah
 *
 * Use Case  : Mengelola Jadwal Pengangkutan Sampah
 * Aktor     : Admin
 * Awal      : Admin sudah login dan mengakses menu jadwal pengangkutan
 * Akhir     : Jadwal tersimpan dan dapat dilihat pengguna
 */
class MengelolaJadwalPengangkutanTest extends TestCase
{
    use RefreshDatabase;

    private KategoriSampah $organik;

    private LokasiTempatSampah $a2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organik = KategoriSampah::create(['nama_kategori' => 'Organik', 'kode' => 'ORG', 'warna_label' => '#2F855A', 'status' => 'aktif']);
        $this->a2 = LokasiTempatSampah::create([
            'nama_lokasi' => 'Kantin TULT - A2', 'kode_titik' => 'A2', 'area' => 'Kantin TULT',
            'kode_perangkat' => 'PRK-A2', 'status' => 'aktif',
        ]);

        $this->actingAs(Pengguna::factory()->admin()->create());
    }

    /** Kategori Organik, lokasi A2, Senin, jam angkut 07:00. */
    private function dataValid(array $override = []): array
    {
        return $override + [
            'id_kategori' => $this->organik->id_kategori,
            'id_lokasi' => [$this->a2->id_lokasi],
            'pola' => 'mingguan',
            'hari' => 'senin',
            'berlaku_mulai' => '2026-11-16',
            'jam_buang_mulai' => '05:00',
            'jam_buang_selesai' => '06:30',
            'jam_angkut' => '07:00',
            'catatan' => 'Angkut sebelum kelas pagi',
        ];
    }

    private function jadwalTersimpan(): Schedule
    {
        $this->post(route('admin.schedules.store'), $this->dataValid())->assertSessionHasNoErrors();

        return Schedule::query()->latest('id_jadwal')->firstOrFail();
    }

    // ------------------------------------------------------------------ Test Case

    /** TC-JDW-01 · Admin membuka menu jadwal → tabel jadwal tampil. */
    public function test_tc_jdw_01_menampilkan_daftar_jadwal_pengangkutan(): void
    {
        $this->jadwalTersimpan();

        $this->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('Jadwal Pengangkutan')
            ->assertSee('Organik')
            ->assertSee('Kantin TULT - A2')
            ->assertSee('<td>07:00</td>', false)
            ->assertSee('Aktif');
    }

    /** TC-JDW-02 · Kategori Organik, lokasi A2, Senin, 07:00 → tersimpan status Aktif, pesan berhasil. */
    public function test_tc_jdw_02_menambah_jadwal_dengan_data_valid(): void
    {
        $this->post(route('admin.schedules.store'), $this->dataValid())
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('success', 'Jadwal sudah berhasil disimpan');

        $jadwal = Schedule::with('locations')->firstOrFail();
        $this->assertSame('aktif', $jadwal->status);
        $this->assertSame('senin', $jadwal->hari);
        $this->assertSame($this->organik->id_kategori, $jadwal->id_kategori);
        $this->assertSame([$this->a2->id_lokasi], $jadwal->locations->pluck('id_lokasi')->all());
        $this->assertStringStartsWith('07:00', $jadwal->jam_angkut);
    }

    /** TC-JDW-03 · Lokasi dikosongkan → ditolak, pesan field wajib diisi. */
    public function test_tc_jdw_03_menambah_jadwal_dengan_field_kosong(): void
    {
        $data = $this->dataValid();
        unset($data['id_lokasi']);

        $this->from(route('admin.schedules.index'))
            ->post(route('admin.schedules.store'), $data)
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHasErrors(['id_lokasi' => 'Lokasi wajib diisi. Pilih setidaknya satu lokasi atau pilih Semua area.']);

        $this->post(route('admin.schedules.store'), $this->dataValid(['id_kategori' => '']))
            ->assertSessionHasErrors(['id_kategori' => 'Kategori wajib diisi.']);

        $this->assertDatabaseCount('jadwal_pengangkutan', 0);
    }

    /** TC-JDW-04 · Hari "Funday" / jam "25:00" → ditolak, pesan hari/jam tidak valid. */
    public function test_tc_jdw_04_menambah_jadwal_dengan_hari_atau_jam_tidak_valid(): void
    {
        $this->post(route('admin.schedules.store'), $this->dataValid(['hari' => 'Funday']))
            ->assertSessionHasErrors(['hari' => 'Hari tidak valid.']);

        $this->post(route('admin.schedules.store'), $this->dataValid(['jam_angkut' => '25:00']))
            ->assertSessionHasErrors(['jam_angkut' => 'Jam tidak valid. Gunakan format JJ:MM (00:00–23:59).']);

        $this->assertDatabaseCount('jadwal_pengangkutan', 0);
    }

    /** TC-JDW-05 · Ubah jam menjadi 08:00 → perubahan tersimpan. */
    public function test_tc_jdw_05_mengubah_jadwal_dengan_data_valid(): void
    {
        $jadwal = $this->jadwalTersimpan();

        $this->put(route('admin.schedules.update', $jadwal), $this->dataValid(['jam_angkut' => '08:00']))
            ->assertRedirect()
            ->assertSessionHas('success', 'Jadwal sudah berhasil disimpan');

        $this->assertStringStartsWith('08:00', $jadwal->fresh()->jam_angkut);
        $this->assertDatabaseCount('jadwal_pengangkutan', 1);
    }

    /** TC-JDW-06 · Klik Nonaktifkan → Ya → status Nonaktif, data tetap ada. */
    public function test_tc_jdw_06_menonaktifkan_jadwal_dengan_konfirmasi(): void
    {
        $jadwal = $this->jadwalTersimpan();

        $this->get(route('admin.schedules.index'))->assertSee("return confirm('Yakin ingin", false);

        $this->patch(route('admin.schedules.status', $jadwal), ['status' => 'nonaktif'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Jadwal berhasil dinonaktifkan.');

        $this->assertSame('nonaktif', $jadwal->fresh()->status);
        $this->assertModelExists($jadwal);
    }

    /** TC-JDW-07 · User biasa membuka menu kelola jadwal → 403. */
    public function test_tc_jdw_07_non_admin_mengakses_menu_kelola_jadwal(): void
    {
        $this->actingAs(Pengguna::factory()->create());

        $this->get(route('admin.schedules.index'))->assertForbidden();
        $this->post(route('admin.schedules.store'), $this->dataValid())->assertForbidden();
        $this->assertDatabaseCount('jadwal_pengangkutan', 0);
    }

    // ------------------------------------------------------------------ Use Case

    /** Skenario Normal: buka menu → isi form dan Simpan → Nonaktifkan dengan konfirmasi. */
    public function test_use_case_skenario_normal(): void
    {
        // 1–2. Membuka menu jadwal → daftar jadwal tampil.
        $this->get(route('admin.schedules.index'))->assertOk()->assertSee('Belum ada jadwal');

        // 3–4. Mengisi form dan klik Simpan → divalidasi dan disimpan.
        $jadwal = $this->jadwalTersimpan();
        $this->assertSame('aktif', $jadwal->status);

        // 5–6. Klik Nonaktifkan dan konfirmasi → status Nonaktif.
        $this->patch(route('admin.schedules.status', $jadwal), ['status' => 'nonaktif']);
        $this->assertSame('nonaktif', $jadwal->fresh()->status);

        // Jadwal Nonaktif tidak tampil ke pengguna (PBI-08). 16 Nov 2026 adalah hari Senin.
        $this->get(route('schedules.public', ['tanggal' => '2026-11-16']))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
            ->assertSee('Jadwal belum tersedia');
    }

    /** Skenario Gagal: field kosong / hari-jam tidak valid ditolak; non-Admin ditolak. */
    public function test_use_case_skenario_gagal(): void
    {
        // 1–2. Menyimpan dengan field kosong / hari-jam tidak valid → ditolak dengan pesan.
        $this->post(route('admin.schedules.store'), [])
            ->assertSessionHasErrors(['id_kategori', 'pola', 'berlaku_mulai', 'jam_angkut']);
        $this->post(route('admin.schedules.store'), $this->dataValid(['hari' => 'Funday', 'jam_angkut' => '25:00']))
            ->assertSessionHasErrors(['hari', 'jam_angkut']);

        // 3–4. Non-Admin membuka menu → akses ditolak.
        $this->actingAs(Pengguna::factory()->create())->get(route('admin.schedules.index'))->assertForbidden();

        $this->assertDatabaseCount('jadwal_pengangkutan', 0);
    }
}
