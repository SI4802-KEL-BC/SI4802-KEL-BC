<?php

namespace Tests\Feature\Pbi15;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use App\Models\TransaksiPoin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PBI-15 · TC-15-07, 08, 09 (akses halaman detail & endpoint status) + isi JSON {status, poin}
 * dan skrip pembaruan otomatis (TC-15-04 bagian JSON).
 */
class AksesStatusTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $a;

    private Pengguna $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->a = Pengguna::factory()->create();
        $this->b = Pengguna::factory()->create();
    }

    private function milik(Pengguna $p, array $state = []): AktivitasPemilahan
    {
        return AktivitasPemilahan::factory()->create($state + ['id_pengguna' => $p->id_pengguna]);
    }

    public function test_tc_15_07_pengguna_lain_tidak_bisa_melihat_detail_maupun_status(): void
    {
        $milikB = $this->milik($this->b);

        $this->actingAs($this->a)->get('/aktivitas/'.$milikB->id_aktivitas)->assertForbidden();
        $this->actingAs($this->a)->getJson('/aktivitas/'.$milikB->id_aktivitas.'/status')->assertForbidden();
        $this->actingAs($this->a)->get('/aktivitas/'.$milikB->id_aktivitas.'/status')->assertForbidden();
    }

    public function test_tc_15_08_id_tidak_ada_menghasilkan_404(): void
    {
        $this->actingAs($this->a)->get('/aktivitas/99999')->assertNotFound();
        $this->actingAs($this->a)->getJson('/aktivitas/99999/status')->assertNotFound();
    }

    public function test_tc_15_09_tanpa_login_diarahkan_ke_login(): void
    {
        $akt = $this->milik($this->a);

        $this->get('/aktivitas/'.$akt->id_aktivitas)->assertRedirect(route('login'));
        $this->get('/aktivitas/'.$akt->id_aktivitas.'/status')->assertRedirect(route('login'));
        $this->getJson('/aktivitas/'.$akt->id_aktivitas.'/status')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_tc_15_04_json_status_menunggu_validasi_tanpa_poin(): void
    {
        $akt = $this->milik($this->a);

        $this->actingAs($this->a)->getJson(route('aktivitas.status', $akt))
            ->assertOk()
            ->assertExactJson(['status' => 'Menunggu Validasi', 'poin' => null])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_json_status_terverifikasi_membawa_poin_dari_transaksi(): void
    {
        $akt = $this->milik($this->a, ['status_verifikasi' => 'Terverifikasi', 'poin_diperoleh' => 99]);
        TransaksiPoin::factory()->create([
            'id_pengguna' => $this->a->id_pengguna, 'id_aktivitas' => $akt->id_aktivitas, 'jumlah_poin' => 10,
        ]);

        $this->actingAs($this->a)->getJson(route('aktivitas.status', $akt))
            ->assertOk()
            ->assertExactJson(['status' => 'Terverifikasi', 'poin' => 10]);
    }

    public function test_json_status_ditolak_poin_nol(): void
    {
        $akt = $this->milik($this->a, ['status_verifikasi' => 'Ditolak', 'alasan_penolakan' => 'Kategori tidak sesuai tempat sampah']);

        $this->actingAs($this->a)->getJson(route('aktivitas.status', $akt))
            ->assertOk()
            ->assertExactJson(['status' => 'Ditolak', 'poin' => 0]);
    }

    public function test_json_mengikuti_perubahan_status_di_database(): void
    {
        $akt = $this->milik($this->a);
        $this->actingAs($this->a)->getJson(route('aktivitas.status', $akt))->assertJsonPath('status', 'Menunggu Validasi');

        $akt->update(['status_verifikasi' => 'Ditolak', 'alasan_penolakan' => 'Foto buram']);

        $this->actingAs($this->a)->getJson(route('aktivitas.status', $akt))->assertJsonPath('status', 'Ditolak');
    }

    public function test_skrip_pembaruan_otomatis_hanya_ada_saat_menunggu_validasi(): void
    {
        $menunggu = $this->milik($this->a);
        $selesai = $this->milik($this->a, ['status_verifikasi' => 'Terverifikasi']);
        $ditolak = $this->milik($this->a, ['status_verifikasi' => 'Ditolak', 'alasan_penolakan' => 'x']);

        $this->actingAs($this->a)->get('/aktivitas/'.$menunggu->id_aktivitas)
            ->assertOk()
            ->assertSee('id="skrip-status-otomatis"', false)
            ->assertSee(route('aktivitas.status', $menunggu), false)
            ->assertSee('setInterval(periksa, 10000)', false)
            ->assertSee('Status diperiksa otomatis setiap 10 detik');

        foreach ([$selesai, $ditolak] as $akt) {
            $this->actingAs($this->a)->get('/aktivitas/'.$akt->id_aktivitas)
                ->assertOk()
                ->assertDontSee('skrip-status-otomatis', false)
                ->assertDontSee('Status diperiksa otomatis');
        }
    }
}
