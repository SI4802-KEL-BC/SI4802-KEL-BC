<?php

namespace Tests\Feature\Pbi14;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Pbi14\Concerns\BuatAktivitas;
use Tests\TestCase;

/** PBI-14 · TC-14-01, 02, 05, 06, 09, 10 (+ ringkasan, eager loading, menu). */
class RiwayatAktivitasTest extends TestCase
{
    use BuatAktivitas;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tc_14_01_menampilkan_riwayat_milik_sendiri_urut_terbaru(): void
    {
        $a = Pengguna::factory()->create();
        $b = Pengguna::factory()->create();

        $milikA = $this->buatAktivitas($a, 5, fn (int $i) => ['tanggal_waktu' => now()->subDays(10 - $i)->startOfHour()]);
        $milikB = $this->buatAktivitas($b, 3);

        $resp = $this->actingAs($a)->get('/aktivitas')->assertOk();

        $urut = $resp->viewData('aktivitas')->pluck('id_aktivitas')->all();
        $this->assertSame($milikA->sortByDesc('tanggal_waktu')->pluck('id_aktivitas')->values()->all(), $urut);
        $this->assertCount(5, $urut);

        foreach ($milikA as $item) {
            $resp->assertSee($this->kodeAkt($item));
        }
        foreach ($milikB as $item) {
            $resp->assertDontSee($this->kodeAkt($item));
        }
    }

    public function test_baris_menampilkan_kolom_yang_diminta_dan_format_poin(): void
    {
        $u = Pengguna::factory()->create();
        $terverifikasi = AktivitasPemilahan::factory()->terverifikasi(15)->create([
            'id_pengguna' => $u->id_pengguna,
            'tanggal_waktu' => '2026-10-05 14:30:00',
        ]);
        $ditolak = AktivitasPemilahan::factory()->ditolak()->create(['id_pengguna' => $u->id_pengguna, 'tanggal_waktu' => '2026-10-04 09:00:00']);
        $menunggu = AktivitasPemilahan::factory()->create(['id_pengguna' => $u->id_pengguna, 'tanggal_waktu' => '2026-10-03 09:00:00']);

        $resp = $this->actingAs($u)->get('/aktivitas')->assertOk();

        $resp->assertSee($this->kodeAkt($terverifikasi))
            ->assertSee('05 Okt 2026, 14:30')
            ->assertSee($terverifikasi->kategori->nama_kategori)
            ->assertSee($terverifikasi->lokasi->nama_lokasi)
            ->assertSee('Terverifikasi')
            ->assertSee('Ditolak')
            ->assertSee('Menunggu Validasi')
            ->assertSee('+15')
            ->assertSee($terverifikasi->kategori->warna_label)
            ->assertSee($this->kodeAkt($ditolak))
            ->assertSee($this->kodeAkt($menunggu));
    }

    public function test_tc_14_02_pagination_10_per_halaman(): void
    {
        $a = Pengguna::factory()->create();
        $this->buatAktivitas($a, 15, fn (int $i) => ['tanggal_waktu' => now()->subMinutes(100 - $i)->startOfMinute()]);

        $hal1 = $this->actingAs($a)->get('/aktivitas')->assertOk()->viewData('aktivitas');
        $this->assertCount(10, $hal1);
        $this->assertSame(15, $hal1->total());

        $hal2 = $this->actingAs($a)->get('/aktivitas?page=2')->assertOk()->viewData('aktivitas');
        $this->assertCount(5, $hal2);

        $this->assertEmpty($hal1->pluck('id_aktivitas')->intersect($hal2->pluck('id_aktivitas')));
    }

    public function test_tc_14_05_membuka_detail_dari_riwayat(): void
    {
        $a = Pengguna::factory()->create();
        $akt = $this->buatAktivitas($a)->first();

        $this->actingAs($a)->get('/aktivitas')
            ->assertOk()
            ->assertSee('href="'.route('aktivitas.show', $akt).'"', false);

        $this->actingAs($a)->get('/aktivitas/'.$akt->id_aktivitas)
            ->assertOk()
            ->assertSee($this->kodeAkt($akt));
    }

    public function test_tc_14_06_riwayat_kosong(): void
    {
        $baru = Pengguna::factory()->create();

        $this->actingAs($baru)->get('/aktivitas')
            ->assertOk()
            ->assertSee('Belum ada aktivitas yang tercatat')
            ->assertSee('Buka QR ID Saya')
            ->assertDontSee('Tidak ada aktivitas sesuai filter');
    }

    public function test_tc_14_09_akses_tanpa_login_diarahkan_ke_login(): void
    {
        $this->get('/aktivitas')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_tc_14_10_detail_aktivitas_milik_pengguna_lain_ditolak(): void
    {
        $a = Pengguna::factory()->create();
        $b = Pengguna::factory()->create();
        $milikB = $this->buatAktivitas($b)->first();

        $this->actingAs($a)->get('/aktivitas/'.$milikB->id_aktivitas)->assertForbidden();
    }

    public function test_ringkasan_empat_angka_milik_sendiri_dan_tidak_ikut_filter(): void
    {
        $a = Pengguna::factory()->create();
        $b = Pengguna::factory()->create();

        AktivitasPemilahan::factory()->count(2)->terverifikasi()->create(['id_pengguna' => $a->id_pengguna]);
        AktivitasPemilahan::factory()->count(3)->create(['id_pengguna' => $a->id_pengguna]);
        AktivitasPemilahan::factory()->ditolak()->create(['id_pengguna' => $a->id_pengguna]);
        AktivitasPemilahan::factory()->count(4)->terverifikasi()->create(['id_pengguna' => $b->id_pengguna]);

        $this->assertSame(
            ['total' => 6, 'terverifikasi' => 2, 'menunggu' => 3, 'ditolak' => 1],
            AktivitasPemilahan::ringkasan($a)
        );

        // Dengan filter status, ringkasan tetap menghitung seluruh data milik pengguna.
        $this->actingAs($a)->get('/aktivitas?status=Ditolak')
            ->assertOk()
            ->assertViewHas('ringkasan', ['total' => 6, 'terverifikasi' => 2, 'menunggu' => 3, 'ditolak' => 1]);
    }

    public function test_tidak_ada_n_plus_1_query_saat_data_bertambah(): void
    {
        $a = Pengguna::factory()->create();

        $hitung = function () use ($a): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($a)->get('/aktivitas')->assertOk();
            $jumlah = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $jumlah;
        };

        $this->buatAktivitas($a, 2);
        $sedikit = $hitung();

        $this->buatAktivitas($a, 8);
        $banyak = $hitung();

        $this->assertSame($sedikit, $banyak, 'Jumlah query bertambah seiring data (N+1).');
    }

    public function test_menu_aktivitas_saya_aktif_dan_mengarah_ke_riwayat(): void
    {
        $u = Pengguna::factory()->create();

        $this->actingAs($u)->get('/aktivitas')
            ->assertOk()
            ->assertSee('href="'.url('/aktivitas').'"', false);
    }
}
