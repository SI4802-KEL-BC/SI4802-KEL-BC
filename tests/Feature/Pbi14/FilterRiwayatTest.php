<?php

namespace Tests\Feature\Pbi14;

use App\Models\AktivitasPemilahan;
use App\Models\KategoriSampah;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pbi14\Concerns\BuatAktivitas;
use Tests\TestCase;

/** PBI-14 · TC-14-03, 04, 07, 08 (filter riwayat). */
class FilterRiwayatTest extends TestCase
{
    use BuatAktivitas;
    use RefreshDatabase;

    private Pengguna $a;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->a = Pengguna::factory()->create();
    }

    public function test_tc_14_03_filter_berdasarkan_status(): void
    {
        $this->buatAktivitas($this->a, 2, ['status_verifikasi' => AktivitasPemilahan::DITOLAK]);
        $this->buatAktivitas($this->a, 3, ['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI]);
        $this->buatAktivitas($this->a, 1);

        $hasil = $this->actingAs($this->a)->get('/aktivitas?status=Ditolak')
            ->assertOk()->viewData('aktivitas');

        $this->assertCount(2, $hasil);
        $this->assertSame(['Ditolak'], $hasil->pluck('status_verifikasi')->unique()->values()->all());
    }

    public function test_tc_14_04_rentang_tanggal_dan_kategori_serta_terbawa_di_pagination(): void
    {
        $plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
        $kertas = KategoriSampah::factory()->create(['nama_kategori' => 'Kertas']);

        // Sasaran: 12 Plastik pada Oktober 2026 (2 halaman).
        $sasaran = $this->buatAktivitas($this->a, 12, fn (int $i) => [
            'id_kategori' => $plastik->id_kategori,
            'tanggal_waktu' => '2026-10-'.str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT).' 10:00:00',
        ]);
        // Pengganggu: Plastik di luar rentang, Kertas di dalam rentang.
        $this->buatAktivitas($this->a, 1, ['id_kategori' => $plastik->id_kategori, 'tanggal_waktu' => '2026-09-30 23:59:59']);
        $this->buatAktivitas($this->a, 1, ['id_kategori' => $plastik->id_kategori, 'tanggal_waktu' => '2026-11-01 00:00:00']);
        $this->buatAktivitas($this->a, 2, ['id_kategori' => $kertas->id_kategori, 'tanggal_waktu' => '2026-10-15 10:00:00']);

        $query = '?dari=2026-10-01&sampai=2026-10-31&kategori='.$plastik->id_kategori;

        $hal1 = $this->actingAs($this->a)->get('/aktivitas'.$query)->assertOk();
        $data1 = $hal1->viewData('aktivitas');
        $this->assertSame(12, $data1->total());
        $this->assertCount(10, $data1);
        $this->assertEqualsCanonicalizing(
            $sasaran->pluck('id_aktivitas')->all(),
            collect($data1->items())->pluck('id_aktivitas')
                ->merge($this->actingAs($this->a)->get('/aktivitas'.$query.'&page=2')->viewData('aktivitas')->pluck('id_aktivitas'))
                ->all()
        );

        // Tautan halaman berikutnya membawa seluruh parameter filter.
        $nextUrl = $data1->nextPageUrl();
        $this->assertStringContainsString('dari=2026-10-01', $nextUrl);
        $this->assertStringContainsString('sampai=2026-10-31', $nextUrl);
        $this->assertStringContainsString('kategori='.$plastik->id_kategori, $nextUrl);
        $this->assertStringContainsString('page=2', $nextUrl);
    }

    public function test_batas_rentang_tanggal_bersifat_inklusif(): void
    {
        $awal = $this->buatAktivitas($this->a, 1, ['tanggal_waktu' => '2026-10-01 00:00:00'])->first();
        $akhir = $this->buatAktivitas($this->a, 1, ['tanggal_waktu' => '2026-10-31 23:59:59'])->first();
        $this->buatAktivitas($this->a, 1, ['tanggal_waktu' => '2026-11-01 00:00:00']);

        $ids = $this->actingAs($this->a)->get('/aktivitas?dari=2026-10-01&sampai=2026-10-31')
            ->assertOk()->viewData('aktivitas')->pluck('id_aktivitas')->all();

        $this->assertEqualsCanonicalizing([$awal->id_aktivitas, $akhir->id_aktivitas], $ids);
    }

    public function test_hanya_dari_atau_hanya_sampai_bekerja(): void
    {
        $lama = $this->buatAktivitas($this->a, 1, ['tanggal_waktu' => '2026-01-10 10:00:00'])->first();
        $baru = $this->buatAktivitas($this->a, 1, ['tanggal_waktu' => '2026-10-10 10:00:00'])->first();

        $this->assertSame([$baru->id_aktivitas], $this->actingAs($this->a)->get('/aktivitas?dari=2026-06-01')
            ->viewData('aktivitas')->pluck('id_aktivitas')->all());
        $this->assertSame([$lama->id_aktivitas], $this->actingAs($this->a)->get('/aktivitas?sampai=2026-06-01')
            ->viewData('aktivitas')->pluck('id_aktivitas')->all());
    }

    public function test_filter_tidak_membocorkan_aktivitas_pengguna_lain(): void
    {
        $lain = Pengguna::factory()->create();
        $this->buatAktivitas($lain, 3, ['status_verifikasi' => AktivitasPemilahan::DITOLAK]);

        $this->actingAs($this->a)->get('/aktivitas?status=Ditolak')
            ->assertOk()
            ->assertSee('Tidak ada aktivitas sesuai filter');
    }

    public function test_tc_14_07_filter_tanpa_hasil(): void
    {
        $this->buatAktivitas($this->a, 2); // semua Menunggu Validasi

        $this->actingAs($this->a)->get('/aktivitas?status=Terverifikasi')
            ->assertOk()
            ->assertSee('Tidak ada aktivitas sesuai filter')
            ->assertSee('Reset filter')
            ->assertDontSee('Belum ada aktivitas yang tercatat');
    }

    public function test_tc_14_08_rentang_tanggal_tidak_valid(): void
    {
        $this->buatAktivitas($this->a, 3);

        $resp = $this->actingAs($this->a)
            ->followingRedirects()
            ->get('/aktivitas?dari=2026-10-31&sampai=2026-10-01');

        $resp->assertOk()->assertSee('Tanggal awal harus sebelum tanggal akhir');
        // Data tidak difilter memakai nilai salah: ketiga aktivitas tetap tampil.
        $this->assertCount(3, $resp->viewData('aktivitas'));
    }

    public function test_tc_14_08_status_tidak_dikenal(): void
    {
        $this->buatAktivitas($this->a, 3);

        $this->actingAs($this->a)->get('/aktivitas?status=Asal')
            ->assertRedirect(route('aktivitas.index'))
            ->assertSessionHasErrors('status');

        $resp = $this->actingAs($this->a)->followingRedirects()->get('/aktivitas?status=Asal');
        $resp->assertOk()->assertSee('Status tidak dikenal.');
        $this->assertCount(3, $resp->viewData('aktivitas'));
    }

    public function test_kategori_tidak_dikenal_ditolak_validasi(): void
    {
        $this->actingAs($this->a)->get('/aktivitas?kategori=99999')
            ->assertRedirect(route('aktivitas.index'))
            ->assertSessionHasErrors('kategori');
    }

    public function test_filter_kosong_dianggap_tanpa_filter(): void
    {
        $this->buatAktivitas($this->a, 2);

        $this->actingAs($this->a)->get('/aktivitas?dari=&sampai=&kategori=&status=')
            ->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewHas('adaFilter', false);
    }
}
