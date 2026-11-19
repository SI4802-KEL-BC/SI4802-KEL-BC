<?php

namespace Tests\Feature\PBI15;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use App\Models\TransaksiPoin;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SCRUM-38 · PBI-15 Melihat Status Verifikasi Aktivitas Pemilahan
 *
 * Use Case  : Melihat Status Verifikasi Aktivitas Pemilahan (UC-15)
 * Aktor     : User
 * Awal      : Pengguna sudah login dan punya aktivitas
 * Akhir     : Status verifikasi ditampilkan
 *
 * Test case TC-15-01 s.d. TC-15-09 ada di StatusVerifikasiTest, AksesStatusTest, dan NotifikasiVerifikasiTest.
 * File ini menguji alur use case dari awal sampai akhir.
 */
class MelihatStatusVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->pengguna = Pengguna::factory()->create();
    }

    private function aktivitas(string $status, array $state = []): AktivitasPemilahan
    {
        $aktivitas = AktivitasPemilahan::factory()->create($state + [
            'id_pengguna' => $this->pengguna->id_pengguna,
            'status_verifikasi' => $status,
        ]);
        VerifikasiSampah::factory()->create([
            'id_aktivitas' => $aktivitas->id_aktivitas,
            'hasil' => $status === AktivitasPemilahan::DITOLAK ? 'Tidak Valid' : 'Valid',
        ]);

        return $aktivitas;
    }

    /** Skenario Normal: buka daftar → status tampil → pilih aktivitas → status, label, dan poin tampil. */
    public function test_use_case_skenario_normal(): void
    {
        $aktivitas = $this->aktivitas(AktivitasPemilahan::TERVERIFIKASI);
        TransaksiPoin::factory()->create([
            'id_pengguna' => $this->pengguna->id_pengguna, 'id_aktivitas' => $aktivitas->id_aktivitas, 'jumlah_poin' => 10,
        ]);

        // 1–2. Membuka daftar aktivitas → aktivitas tampil beserta status.
        $this->actingAs($this->pengguna)->get(route('aktivitas.index'))
            ->assertOk()
            ->assertSee('Terverifikasi');

        // 3–4. Memilih aktivitas → status, label, dan poin tampil.
        $this->actingAs($this->pengguna)->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee('Terverifikasi')
            ->assertSee('Label terdeteksi')
            ->assertSee('plastic')
            ->assertSee('+10');

        $this->actingAs($this->pengguna)->getJson(route('aktivitas.status', $aktivitas))
            ->assertOk()
            ->assertExactJson(['status' => AktivitasPemilahan::TERVERIFIKASI, 'poin' => 10]);
    }

    /** Skenario Gagal: memilih aktivitas Ditolak → status Ditolak beserta alasan, poin 0. */
    public function test_use_case_skenario_gagal(): void
    {
        $aktivitas = $this->aktivitas(AktivitasPemilahan::DITOLAK, [
            'alasan_penolakan' => 'Kategori tidak sesuai tempat sampah',
            'poin_diperoleh' => 0,
        ]);

        $this->actingAs($this->pengguna)->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Alasan penolakan')
            ->assertSee('Kategori tidak sesuai tempat sampah');

        $this->actingAs($this->pengguna)->getJson(route('aktivitas.status', $aktivitas))
            ->assertExactJson(['status' => AktivitasPemilahan::DITOLAK, 'poin' => 0]);
    }
}
