<?php

namespace Tests\Feature\Pbi15;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use App\Models\TransaksiPoin;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PBI-15 · TC-15-01, 02, 03, 04, 06 (tampilan status verifikasi). */
class StatusVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $pemilik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->pemilik = Pengguna::factory()->create();
    }

    private function aktivitas(array $state = [], string $metode = 'create'): AktivitasPemilahan
    {
        return AktivitasPemilahan::factory()->create($state + ['id_pengguna' => $this->pemilik->id_pengguna]);
    }

    private function tahap1(AktivitasPemilahan $a, string $hasil = 'Valid', float $skor = 0.92, string $label = 'plastic'): VerifikasiSampah
    {
        return VerifikasiSampah::factory()->create([
            'id_aktivitas' => $a->id_aktivitas, 'tahap' => 1, 'hasil' => $hasil,
            'skor_keyakinan' => $skor, 'label_terdeteksi' => $label, 'waktu_verifikasi' => '2026-10-05 14:30:00',
        ]);
    }

    private function poin(AktivitasPemilahan $a, int $jumlah): void
    {
        TransaksiPoin::factory()->create([
            'id_pengguna' => $a->id_pengguna, 'id_aktivitas' => $a->id_aktivitas, 'jumlah_poin' => $jumlah,
        ]);
    }

    private function detail(AktivitasPemilahan $a)
    {
        return $this->actingAs($this->pemilik)->get('/aktivitas/'.$a->id_aktivitas)->assertOk();
    }

    public function test_tc_15_01_badge_status_di_daftar_tiga_warna(): void
    {
        $this->aktivitas();
        $this->aktivitas(['status_verifikasi' => 'Terverifikasi', 'poin_diperoleh' => 10]);
        $this->aktivitas(['status_verifikasi' => 'Ditolak', 'alasan_penolakan' => 'Kategori tidak sesuai tempat sampah']);

        $html = $this->actingAs($this->pemilik)->get('/aktivitas')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/bg-\[#FEF3C7\][^>]*>\s*<span[^>]*><\/span>\s*Menunggu Validasi/', $html);
        $this->assertMatchesRegularExpression('/bg-\[#E8F7EE\][^>]*>\s*<span[^>]*><\/span>\s*Terverifikasi/', $html);
        $this->assertMatchesRegularExpression('/bg-\[#FEE2E2\][^>]*>\s*<span[^>]*><\/span>\s*Ditolak/', $html);
    }

    public function test_tc_15_02_terverifikasi_otomatis_menampilkan_label_sumber_dan_poin_transaksi(): void
    {
        $a = $this->aktivitas(['status_verifikasi' => 'Terverifikasi', 'poin_diperoleh' => 99]);
        $this->tahap1($a);
        $this->poin($a, 10);

        $resp = $this->detail($a);

        $resp->assertSee('Sumber verifikasi')
            ->assertSee('Otomatis')
            ->assertSee('Label terdeteksi')
            ->assertSee('plastic')
            ->assertSee('92%')
            ->assertSee('Aktivitas terverifikasi · +10 poin')
            ->assertSee('05 Okt 2026, 14:30')
            ->assertDontSee('Diverifikasi Admin');
        // Poin berasal dari transaksi_poin (10), bukan poin_diperoleh (99).
        $this->assertStringNotContainsString('+99', $resp->getContent());
    }

    public function test_tc_15_03_terverifikasi_oleh_admin_menampilkan_nama_admin(): void
    {
        $admin = Pengguna::factory()->admin()->create(['nama_lengkap' => 'Admin Uji']);
        $a = $this->aktivitas(['status_verifikasi' => 'Terverifikasi', 'poin_diperoleh' => 10]);
        $this->tahap1($a, 'Ragu', 0.55);
        VerifikasiSampah::factory()->create([
            'id_aktivitas' => $a->id_aktivitas, 'tahap' => 2, 'hasil' => 'Valid', 'id_verifikator' => $admin->id_pengguna,
            'skor_keyakinan' => null, 'label_terdeteksi' => null, 'waktu_verifikasi' => '2026-10-06 09:15:00',
        ]);
        $this->poin($a, 10);

        $this->detail($a)
            ->assertSee('Diverifikasi Admin · Admin Uji', false)
            ->assertSee('06 Okt 2026, 09:15')
            ->assertSee('plastic') // label tetap dari identifikasi otomatis
            ->assertSee('+10 poin');

        $this->assertSame('Diverifikasi Admin', AktivitasPemilahan::find($a->id_aktivitas)->lihatStatus()['sumber']);
    }

    public function test_tc_15_04_menunggu_validasi_ditinjau_admin_tanpa_poin(): void
    {
        $a = $this->aktivitas();
        $this->tahap1($a, 'Ragu', 0.55);

        $resp = $this->detail($a);

        $resp->assertSee('Menunggu Validasi')
            ->assertSee('Sedang ditinjau Admin')
            ->assertDontSee('Sumber verifikasi')
            ->assertDontSee('Aktivitas terverifikasi')
            ->assertDontSee('poin ditambahkan') // "+N poin ditambahkan" hanya untuk Terverifikasi
            ->assertDontSee('+0');             // poin belum tampil

        $info = AktivitasPemilahan::find($a->id_aktivitas)->lihatStatus();
        $this->assertNull($info['poin']);
        $this->assertNull($info['sumber']);
    }

    public function test_menunggu_tanpa_hasil_tahap_1_tampil_sedang_diproses(): void
    {
        $a = $this->aktivitas();

        $this->detail($a)
            ->assertSee('Sedang diproses')
            ->assertDontSee('Sedang ditinjau Admin');
    }

    public function test_tc_15_06_ditolak_menampilkan_alasan_dan_poin_nol(): void
    {
        $a = $this->aktivitas(['status_verifikasi' => 'Ditolak', 'alasan_penolakan' => 'Kategori tidak sesuai tempat sampah', 'poin_diperoleh' => 0]);
        $this->tahap1($a, 'Tidak Valid', 0.9);

        $this->detail($a)
            ->assertSee('Aktivitas ditolak')
            ->assertSee('Alasan penolakan')
            ->assertSee('Kategori tidak sesuai tempat sampah')
            ->assertSee('Otomatis')
            ->assertDontSee('ditambahkan');

        $info = AktivitasPemilahan::find($a->id_aktivitas)->lihatStatus();
        $this->assertSame(0, $info['poin']);
        $this->assertSame('Kategori tidak sesuai tempat sampah', $info['alasan']);
    }

    public function test_terverifikasi_tanpa_transaksi_poin_menampilkan_nol_bukan_angka_lain(): void
    {
        $a = $this->aktivitas(['status_verifikasi' => 'Terverifikasi', 'poin_diperoleh' => 25]);
        $this->tahap1($a);

        $this->assertSame(0, AktivitasPemilahan::find($a->id_aktivitas)->lihatStatus()['poin']);
    }

    public function test_verifikasi_terakhir_adalah_tahap_tertinggi(): void
    {
        $admin = Pengguna::factory()->admin()->create();
        $a = $this->aktivitas();
        $v1 = $this->tahap1($a, 'Ragu', 0.5);
        $v2 = VerifikasiSampah::factory()->create([
            'id_aktivitas' => $a->id_aktivitas, 'tahap' => 2, 'hasil' => 'Tidak Valid', 'id_verifikator' => $admin->id_pengguna,
        ]);

        $this->assertTrue($a->fresh()->verifikasiTerakhir->is($v2));
        $this->assertFalse($a->fresh()->verifikasiTerakhir->is($v1));
    }
}
