<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Services\PindaiTongService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pbi13\Concerns\SiapkanPerangkat;
use Tests\TestCase;

/**
 * TC-13-01, 02, 09, 10, 14 untuk alur: QR tertempel di TONG sampah, pengguna memindainya (HP/laptop) lalu
 * kamera tong mengirim foto. (Menggantikan alur lama "pengguna menampilkan QR, perangkat memindai".)
 */
class QrSessionTest extends TestCase
{
    use RefreshDatabase;
    use SiapkanPerangkat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanPerangkat();
        $this->withoutVite();
    }

    // ----------------------------------------------------------------- TC-13-01

    public function test_tc_13_01_halaman_pindai_menampilkan_pemindai_dan_input_kode_tong(): void
    {
        $this->actingAs($this->pengguna)
            ->get('/pindai')
            ->assertOk()
            ->assertSee('Pindai QR Tong Sampah')
            ->assertSee('Nyalakan kamera')
            ->assertSee('Atau masukkan kode tong')
            ->assertSee('name="kode_perangkat"', false)
            ->assertSee('Belum ada sesi aktif');
    }

    public function test_tc_13_01_admin_melihat_qr_tiap_tong_berisi_url_pindai(): void
    {
        $admin = Pengguna::factory()->admin()->create();
        $lain = LokasiTempatSampah::factory()->create(['nama_lokasi' => 'Gedung Rektorat']);

        $html = $this->actingAs($admin)->get('/admin/qr-tong')->assertOk()
            ->assertSee('QR Tong Sampah')
            ->assertSee($this->lokasi->kode_perangkat)
            ->assertSee('Gedung Rektorat')
            ->assertSee(url('/pindai/'.$this->lokasi->kode_perangkat), false)
            ->assertSee(url('/pindai/'.$lain->kode_perangkat), false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'aria-label="QR tong '), 'Tiap tong punya satu gambar QR.');
        $this->assertSame(2, preg_match_all('/<svg[^>]*viewBox="0 0 240 240"/', $html), 'Gambar QR berukuran 240.');
    }

    public function test_tc_13_01_qr_tong_bisa_dikodekan_dengan_alamat_dasar_lain_untuk_dipindai_dari_hp(): void
    {
        config(['pilahku.qr_base_url' => 'http://192.168.1.10:8000/']);

        $this->assertSame(
            'http://192.168.1.10:8000/pindai/'.$this->lokasi->kode_perangkat,
            app(PindaiTongService::class)->urlQr($this->lokasi),
        );

        $this->actingAs(Pengguna::factory()->admin()->create())
            ->get('/admin/qr-tong')
            ->assertSee('http://192.168.1.10:8000/pindai/'.$this->lokasi->kode_perangkat, false)
            ->assertDontSee('PILAHKU_QR_BASE_URL'); // peringatan hanya tampil bila alamat dasar belum diisi
    }

    public function test_tc_13_01_halaman_qr_tong_hanya_untuk_admin(): void
    {
        $this->actingAs($this->pengguna)->get('/admin/qr-tong')->assertForbidden();

        auth()->logout();
        $this->get('/admin/qr-tong')->assertRedirect(route('login'));
    }

    // ----------------------------------------------------------------- TC-13-02

    public function test_tc_13_02_pindai_qr_tong_membuat_sesi_aktif_atas_nama_pengguna(): void
    {
        $this->freezeSecond(); // kolom DATETIME tidak menyimpan mikrodetik

        $this->pindai()->assertRedirect(route('pindai.index'));

        $sesi = SesiPembuangan::sole();
        $this->assertSame($this->pengguna->id_pengguna, $sesi->id_pengguna);
        $this->assertSame($this->lokasi->id_lokasi, $sesi->id_lokasi);
        $this->assertSame(SesiPembuangan::AKTIF, $sesi->status_sesi);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $sesi->token_qr);
        $this->assertTrue($sesi->waktu_scan->equalTo(now()));
        $this->assertTrue($sesi->kedaluwarsa_pada->equalTo(now()->addSeconds(config('pilahku.session_ttl'))));
    }

    public function test_tc_13_02_setelah_memindai_halaman_menampilkan_sesi_aktif_dan_hitung_mundur(): void
    {
        $this->freezeSecond();
        $this->lokasi->update(['nama_lokasi' => 'Kantin TULT']);

        $this->actingAs($this->pengguna)->followingRedirects()
            ->get('/pindai/'.$this->lokasi->kode_perangkat)
            ->assertOk()
            ->assertSee('QR berhasil dipindai di Kantin TULT')
            ->assertSee('data-sisa-detik="'.config('pilahku.session_ttl').'"', false)
            ->assertSee('05:00')
            ->assertDontSee('Belum ada sesi aktif');
    }

    public function test_tc_13_02_memindai_tong_yang_sama_lagi_melanjutkan_sesi_tanpa_membuat_sesi_baru(): void
    {
        $this->pindai()->assertRedirect();
        $this->actingAs($this->pengguna)->followingRedirects()
            ->get('/pindai/'.$this->lokasi->kode_perangkat)
            ->assertSee('dilanjutkan');

        $this->assertSame(1, SesiPembuangan::count());
    }

    public function test_tc_13_02_input_kode_tong_manual_sama_dengan_memindai(): void
    {
        $this->actingAs($this->pengguna)
            ->post('/pindai', ['kode_perangkat' => $this->lokasi->kode_perangkat])
            ->assertRedirect(route('pindai.index'))
            ->assertSessionHas('status');

        $this->assertSame(SesiPembuangan::AKTIF, SesiPembuangan::sole()->status_sesi);
    }

    // ----------------------------------------------------------------- TC-13-09

    public function test_tc_13_09_kode_tong_tidak_dikenal_ditolak_dengan_pesan_jelas(): void
    {
        $this->actingAs($this->pengguna)
            ->get('/pindai/TIDAK-ADA')
            ->assertRedirect(route('pindai.index'))
            ->assertSessionHasErrors('kode_perangkat');

        $this->actingAs($this->pengguna)->followingRedirects()
            ->get('/pindai/TIDAK-ADA')
            ->assertSee('Kode tong &quot;TIDAK-ADA&quot; tidak dikenali', false)
            ->assertSee('QR belum bisa dipakai');

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_09_kode_dengan_karakter_berbahaya_tidak_cocok_dengan_route(): void
    {
        $this->actingAs($this->pengguna)->get('/pindai/'.rawurlencode('<script>alert(1)</script>'))->assertNotFound();
        $this->actingAs($this->pengguna)->get('/pindai/a.b')->assertNotFound();

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_09_tong_nonaktif_ditolak(): void
    {
        $this->lokasi->update(['status' => 'nonaktif']);

        $this->pindai()->assertSessionHasErrors(['kode_perangkat' => 'Tong sampah ini sedang tidak aktif.']);

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_09_tong_yang_sedang_dipakai_pengguna_lain_ditolak(): void
    {
        $this->pindai()->assertRedirect();

        $this->pindai(Pengguna::factory()->create())
            ->assertSessionHasErrors(['kode_perangkat' => 'Tong ini sedang dipakai pengguna lain. Coba lagi beberapa menit lagi.']);

        $this->assertSame(1, SesiPembuangan::count());
        $this->assertSame($this->pengguna->id_pengguna, SesiPembuangan::sole()->id_pengguna);
    }

    public function test_tc_13_09_pengguna_dengan_sesi_aktif_di_tong_lain_ditolak(): void
    {
        $tongLain = LokasiTempatSampah::factory()->create(['nama_lokasi' => 'Gedung Rektorat']);
        $this->pindai()->assertRedirect();

        $this->pindai($this->pengguna, $tongLain->kode_perangkat)
            ->assertSessionHasErrors(['kode_perangkat' => 'Kamu masih punya sesi aktif di '.$this->lokasi->nama_lokasi.'. Selesaikan atau tunggu sampai berakhir.']);

        $this->assertSame(1, SesiPembuangan::count());
    }

    public function test_tc_13_09_sesi_yang_sudah_lewat_batas_tidak_menghalangi_pengguna_lain(): void
    {
        $this->pindai()->assertRedirect();
        $this->travel(config('pilahku.session_ttl') + 1)->seconds(); // sesi pertama habis tanpa foto

        $this->pindai(Pengguna::factory()->create())->assertSessionHasNoErrors();

        $this->assertSame(2, SesiPembuangan::count());
    }

    public function test_tc_13_09_tanpa_login_diarahkan_ke_login_dan_tidak_membuat_sesi(): void
    {
        $this->get('/pindai/'.$this->lokasi->kode_perangkat)->assertRedirect(route('login'));
        $this->post('/pindai', ['kode_perangkat' => $this->lokasi->kode_perangkat])->assertRedirect(route('login'));

        $this->assertDatabaseCount('sesi_pembuangan', 0);
    }

    public function test_tc_13_09_input_manual_wajib_diisi(): void
    {
        $this->actingAs($this->pengguna)
            ->post('/pindai', ['kode_perangkat' => ''])
            ->assertSessionHasErrors(['kode_perangkat' => 'Masukkan kode tong sampah.']);
    }

    // ----------------------------------------------------------------- TC-13-10

    public function test_tc_13_10_sesi_tanpa_foto_menjadi_kedaluwarsa_tanpa_aktivitas(): void
    {
        $this->pindai()->assertRedirect();
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
        $this->pindai()->assertRedirect();

        $this->travel(config('pilahku.session_ttl') + 1)->seconds();

        $this->kirimFoto()
            ->assertStatus(422)
            ->assertJsonPath('message', 'Sesi pembuangan telah kedaluwarsa');

        $this->assertSame(SesiPembuangan::KEDALUWARSA, SesiPembuangan::sole()->status_sesi);
        $this->assertSame(0, AktivitasPemilahan::count());
    }

    // ----------------------------------------------------------------- TC-13-14

    public function test_tc_13_14_foto_tanpa_token_perangkat_401(): void
    {
        $this->pindai()->assertRedirect();

        $this->kirimFoto(null, [])->assertUnauthorized();

        $this->assertSame(0, AktivitasPemilahan::count());
        $this->assertSame(SesiPembuangan::AKTIF, SesiPembuangan::sole()->status_sesi);
    }

    public function test_tc_13_14_foto_dengan_token_perangkat_salah_401(): void
    {
        $this->pindai()->assertRedirect();

        $this->kirimFoto(null, ['X-Device-Token' => 'salah'])->assertUnauthorized();

        $this->assertSame(0, AktivitasPemilahan::count());
    }

    public function test_tc_13_14_endpoint_scan_lama_sudah_tidak_ada(): void
    {
        // Perangkat tidak lagi memindai QR pengguna; pengguna yang memindai QR tong lewat web.
        $this->postJson('/api/perangkat/scan', ['token_qr' => 'x', 'kode_perangkat' => $this->lokasi->kode_perangkat], $this->headerPerangkat())
            ->assertNotFound();
    }

    // ------------------------------------------- API foto: pencocokan sesi dengan tong

    public function test_foto_untuk_tong_tanpa_sesi_aktif_ditolak(): void
    {
        $this->kirimFoto()
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tidak ada sesi aktif di tong ini. Pindai QR tong terlebih dahulu');

        $this->assertSame(0, AktivitasPemilahan::count());
    }

    public function test_foto_untuk_kode_perangkat_tidak_terdaftar_ditolak(): void
    {
        $this->pindai()->assertRedirect();

        $this->kirimFoto('DEV-TIDAK-ADA')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Perangkat tidak terdaftar');
    }

    public function test_foto_diterima_untuk_sesi_pengguna_di_tong_yang_mengirim_bukan_tong_lain(): void
    {
        $pengguna2 = Pengguna::factory()->create();
        $tong2 = LokasiTempatSampah::factory()->create();
        $this->pindai()->assertRedirect();
        $this->pindai($pengguna2, $tong2->kode_perangkat)->assertRedirect();

        $this->kirimFoto($tong2->kode_perangkat)->assertCreated();

        $aktivitas = AktivitasPemilahan::sole();
        $this->assertSame($pengguna2->id_pengguna, $aktivitas->id_pengguna);
        $this->assertSame($tong2->id_lokasi, $aktivitas->id_lokasi);
        // Sesi pengguna pertama di tong 1 tetap Aktif.
        $this->assertSame(SesiPembuangan::AKTIF, SesiPembuangan::where('id_pengguna', $this->pengguna->id_pengguna)->sole()->status_sesi);
    }

    public function test_foto_tanpa_kode_perangkat_ditolak_validasi(): void
    {
        $this->pindai()->assertRedirect();

        $this->postJson('/api/perangkat/foto', ['foto' => $this->fotoPalsu()], $this->headerPerangkat())
            ->assertStatus(422)
            ->assertJsonValidationErrors('kode_perangkat');
    }
}
