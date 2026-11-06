<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\SesiPembuangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pbi13\Concerns\SiapkanPerangkat;
use Tests\TestCase;

/** TC-13-02, 09, 10, 14 (TC-13-01 diuji bersama halaman QR ID di tahap 7). */
class QrSessionTest extends TestCase
{
    use RefreshDatabase;
    use SiapkanPerangkat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanPerangkat();
    }

    public function test_tc_13_02_pindai_qr_valid_membuat_sesi_aktif(): void
    {
        $this->freezeSecond(); // kolom DATETIME tidak menyimpan mikrodetik
        $token = $this->buatQr();

        $this->scan($token)
            ->assertOk()
            ->assertJsonPath('data.status_sesi', 'Aktif');

        $sesi = SesiPembuangan::sole();
        $this->assertSame($this->pengguna->id_pengguna, $sesi->id_pengguna);
        $this->assertSame($this->lokasi->id_lokasi, $sesi->id_lokasi);
        $this->assertSame(SesiPembuangan::AKTIF, $sesi->status_sesi);
        $this->assertSame(hash('sha256', $token), $sesi->token_qr);
        $this->assertTrue($sesi->kedaluwarsa_pada->equalTo(now()->addSeconds(config('pilahku.session_ttl'))));
    }

    public function test_tc_13_09_qr_acak_ditolak(): void
    {
        $this->scan('token-acak-bukan-qr')
            ->assertStatus(422)
            ->assertJsonPath('message', 'QR tidak valid atau kedaluwarsa');

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_09_qr_kedaluwarsa_ditolak(): void
    {
        $token = $this->buatQr();

        $this->travel(config('pilahku.qr_ttl') + 1)->seconds();

        $this->scan($token)
            ->assertStatus(422)
            ->assertJsonPath('message', 'QR tidak valid atau kedaluwarsa');

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_09_qr_masih_berlaku_tepat_sebelum_batas(): void
    {
        $token = $this->buatQr();

        $this->travel(config('pilahku.qr_ttl') - 1)->seconds();

        $this->scan($token)->assertOk();
    }

    public function test_tc_13_09_qr_sudah_dipakai_ditolak(): void
    {
        $token = $this->buatQr();

        $this->scan($token)->assertOk();
        $this->scan($token)
            ->assertStatus(422)
            ->assertJsonPath('message', 'QR tidak valid atau kedaluwarsa');

        $this->assertDatabaseCount('sesi_pembuangan', 1);
    }

    public function test_tc_13_09_perangkat_tidak_terdaftar_ditolak(): void
    {
        $this->scan($this->buatQr(), 'DEV-TIDAK-ADA')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Perangkat tidak terdaftar');

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_10_sesi_tanpa_foto_menjadi_kedaluwarsa_tanpa_aktivitas(): void
    {
        $this->scan($this->buatQr())->assertOk();
        $sesiTerbaru = SesiPembuangan::sole();

        // Sesi lain yang masih berlaku tidak boleh ikut berubah.
        $this->travel(config('pilahku.session_ttl') - 60)->seconds();
        $masihBerlaku = SesiPembuangan::factory()->create();

        $this->travel(61)->seconds();
        $this->artisan('sesi:kedaluwarsa')->assertSuccessful();

        $this->assertSame(SesiPembuangan::KEDALUWARSA, $sesiTerbaru->fresh()->status_sesi);
        $this->assertSame(SesiPembuangan::AKTIF, $masihBerlaku->fresh()->status_sesi);
        $this->assertSame(0, AktivitasPemilahan::count());
    }

    public function test_tc_13_10_sesi_selesai_tidak_diubah_scheduler(): void
    {
        $sesi = SesiPembuangan::factory()->kedaluwarsaLewat()->create(['status_sesi' => SesiPembuangan::SELESAI]);

        $this->artisan('sesi:kedaluwarsa')->assertSuccessful();

        $this->assertSame(SesiPembuangan::SELESAI, $sesi->fresh()->status_sesi);
    }

    public function test_tc_13_10_foto_setelah_batas_waktu_ditolak_dan_tidak_membuat_aktivitas(): void
    {
        $token = $this->buatQr();
        $this->scan($token)->assertOk();

        $this->travel(config('pilahku.session_ttl') + 1)->seconds();

        $this->kirimFoto($token)->assertStatus(422);

        $this->assertSame(SesiPembuangan::KEDALUWARSA, SesiPembuangan::sole()->status_sesi);
        $this->assertSame(0, AktivitasPemilahan::count());
    }

    public function test_tc_13_14_scan_tanpa_token_perangkat_401(): void
    {
        $this->scan($this->buatQr(), null, [])->assertUnauthorized();

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_14_scan_token_perangkat_salah_401(): void
    {
        $this->scan($this->buatQr(), null, ['X-Device-Token' => 'salah'])->assertUnauthorized();

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_14_foto_tanpa_atau_dengan_token_salah_401(): void
    {
        $token = $this->buatQr();
        $this->scan($token)->assertOk();

        $this->kirimFoto($token, [])->assertUnauthorized();
        $this->kirimFoto($token, ['X-Device-Token' => 'salah'])->assertUnauthorized();

        $this->assertSame(0, AktivitasPemilahan::count());
        $this->assertSame(SesiPembuangan::AKTIF, SesiPembuangan::sole()->status_sesi);
    }
}
