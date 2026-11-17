<?php

namespace Tests\Feature\PBI08;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SCRUM-31 · PBI-8 Melihat Jadwal Pengangkutan Sampah
 *
 * Use Case  : Melihat Jadwal Pengangkutan Sampah
 * Aktor     : User
 * Awal      : Pengguna membuka menu jadwal pengangkutan
 * Akhir     : Jadwal yang tersedia ditampilkan
 */
class MelihatJadwalPengangkutanTest extends TestCase
{
    use RefreshDatabase;

    private const SENIN = '2026-11-16';

    private const SELASA = '2026-11-17';

    private KategoriSampah $organik;

    protected function setUp(): void
    {
        parent::setUp();
        // Senin, 16 November 2026 pukul 06.00 WIB.
        $this->travelTo(CarbonImmutable::parse(self::SENIN.' 06:00:00', 'Asia/Jakarta'));
        $this->organik = KategoriSampah::create(['nama_kategori' => 'Organik', 'kode' => 'ORG', 'status' => 'aktif']);
    }

    private function lokasi(string $kode, string $nama): LokasiTempatSampah
    {
        return LokasiTempatSampah::create([
            'nama_lokasi' => $nama, 'kode_titik' => $kode, 'area' => 'Kampus',
            'kode_perangkat' => 'PRK-'.$kode, 'status' => 'aktif',
        ]);
    }

    private function jadwal(LokasiTempatSampah $lokasi, string $hari = 'senin', string $status = 'aktif', string $jamAngkut = '07:00:00'): Schedule
    {
        $jadwal = Schedule::create([
            'id_kategori' => $this->organik->id_kategori,
            'pola' => 'mingguan',
            'hari' => $hari,
            'berlaku_mulai' => '2026-11-01',
            'jam_buang_mulai' => '05:00:00',
            'jam_buang_selesai' => '06:30:00',
            'jam_angkut' => $jamAngkut,
            'kirim_pengingat' => false,
            'semua_area' => false,
            'status' => $status,
        ]);
        $jadwal->locations()->attach($lokasi->id_lokasi);

        return $jadwal;
    }

    /** @return array<int, int> id_jadwal yang tampil di tabel */
    private function idTampil($response): array
    {
        return $response->viewData('rows')->pluck('schedule.id_jadwal')->unique()->sort()->values()->all();
    }

    // ------------------------------------------------------------------ Test Case

    /** TC-LJD-01 · Ada 3 jadwal aktif dan 1 nonaktif → 3 jadwal aktif tampil. */
    public function test_tc_ljd_01_menampilkan_seluruh_jadwal_aktif(): void
    {
        $aktif = collect([
            $this->jadwal($this->lokasi('A1', 'Kantin TULT A1')),
            $this->jadwal($this->lokasi('A2', 'Kantin TULT A2'), jamAngkut: '08:00:00'),
            $this->jadwal($this->lokasi('B1', 'Gedung Selaru B1'), jamAngkut: '09:00:00'),
        ]);
        $nonaktif = $this->jadwal($this->lokasi('D1', 'Pendopo D1'), status: 'nonaktif');

        $response = $this->get(route('schedules.public'))->assertOk()->assertSee('Organik');

        $this->assertSame($aktif->pluck('id_jadwal')->sort()->values()->all(), $this->idTampil($response));
        $this->assertNotContains($nonaktif->id_jadwal, $this->idTampil($response));
    }

    /** TC-LJD-02 · Pilih tanggal hari Senin → hanya jadwal hari Senin. */
    public function test_tc_ljd_02_memfilter_jadwal_berdasarkan_tanggal(): void
    {
        $senin = $this->jadwal($this->lokasi('A1', 'Kantin TULT A1'), 'senin');
        $selasa = $this->jadwal($this->lokasi('A2', 'Kantin TULT A2'), 'selasa');

        $this->assertSame([$senin->id_jadwal], $this->idTampil($this->get(route('schedules.public', ['tanggal' => self::SENIN]))));
        $this->assertSame([$selasa->id_jadwal], $this->idTampil($this->get(route('schedules.public', ['tanggal' => self::SELASA]))));
    }

    /** TC-LJD-03 · Pilih lokasi A2 → hanya jadwal titik A2. */
    public function test_tc_ljd_03_memfilter_jadwal_berdasarkan_lokasi(): void
    {
        $this->jadwal($this->lokasi('A1', 'Kantin TULT A1'));
        $a2 = $this->lokasi('A2', 'Kantin TULT A2');
        $jadwalA2 = $this->jadwal($a2, jamAngkut: '08:00:00');

        $response = $this->get(route('schedules.public', ['tanggal' => self::SENIN, 'lokasi' => $a2->id_lokasi]))->assertOk();

        $this->assertSame([$jadwalA2->id_jadwal], $this->idTampil($response));
        $this->assertSame(['A2'], $response->viewData('rows')->pluck('point')->unique()->values()->all());
    }

    /** TC-LJD-04 · Pilih tanggal tanpa jadwal → "Jadwal belum tersedia". */
    public function test_tc_ljd_04_jadwal_untuk_periode_yang_dipilih_tidak_ditemukan(): void
    {
        $this->jadwal($this->lokasi('A1', 'Kantin TULT A1'), 'senin');

        $this->get(route('schedules.public', ['tanggal' => self::SELASA]))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
            ->assertSee('Jadwal belum tersedia');
    }

    /** TC-LJD-05 · Database gagal diakses → pesan kesalahan + Muat Ulang. */
    public function test_tc_ljd_05_gagal_memuat_data_jadwal(): void
    {
        Schema::drop('jadwal_lokasi');

        $this->get(route('schedules.public'))
            ->assertStatus(500)
            ->assertSee('Jadwal belum dapat dimuat')
            ->assertSee('Muat ulang jadwal')
            ->assertSee('href="'.route('schedules.public').'"', false);
    }

    // ------------------------------------------------------------------ Use Case

    /** Skenario Normal: buka menu jadwal → jadwal aktif tampil → pilih tanggal/lokasi → hasil sesuai filter. */
    public function test_use_case_skenario_normal(): void
    {
        $a1 = $this->lokasi('A1', 'Kantin TULT A1');
        $a2 = $this->lokasi('A2', 'Kantin TULT A2');
        $jadwalA1 = $this->jadwal($a1);
        $jadwalA2 = $this->jadwal($a2, jamAngkut: '08:00:00');

        // 1–2. Membuka menu jadwal → menampilkan jadwal aktif.
        $this->get(route('home'))->assertSee(route('schedules.public'));
        $this->assertSame(
            collect([$jadwalA1->id_jadwal, $jadwalA2->id_jadwal])->sort()->values()->all(),
            $this->idTampil($this->get(route('schedules.public'))),
        );

        // 3–4. Memilih tanggal/lokasi → menampilkan jadwal sesuai filter.
        $this->assertSame(
            [$jadwalA2->id_jadwal],
            $this->idTampil($this->get(route('schedules.public', ['tanggal' => self::SENIN, 'lokasi' => $a2->id_lokasi]))),
        );
    }

    /** Skenario Gagal: periode tanpa jadwal → "Jadwal belum tersedia"; data gagal dimuat → pesan + muat ulang. */
    public function test_use_case_skenario_gagal(): void
    {
        $this->jadwal($this->lokasi('A1', 'Kantin TULT A1'), 'senin');

        // 1–2. Memilih periode tanpa jadwal.
        $this->get(route('schedules.public', ['tanggal' => self::SELASA]))->assertSee('Jadwal belum tersedia');

        // 3–4. Data gagal dimuat.
        Schema::drop('jadwal_lokasi');
        $this->get(route('schedules.public'))->assertStatus(500)->assertSee('Muat ulang jadwal');
    }
}
