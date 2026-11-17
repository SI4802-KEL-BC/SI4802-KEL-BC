<?php

namespace Tests\Feature\PBI01;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Masuk dan keluar akun memakai tabel pengguna (email_kampus + kata_sandi). */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_halaman_login_menampilkan_form_tanpa_kerangka_sidebar(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('PilahKu')
            ->assertSee('Email Kampus')
            ->assertSee('Kata Sandi')
            ->assertSee('Masuk')
            ->assertSee('action="'.route('login.masuk').'"', false)
            ->assertDontSee('id="sidebar"', false) // tidak memakai sidebar
            ->assertDontSee('Verifikasi Manual');
    }

    public function test_route_login_tetap_bernama_login_untuk_redirect_middleware_auth(): void
    {
        $this->assertSame(url('/login'), route('login'));
        $this->get('/pindai')->assertRedirect(route('login'));
    }

    public function test_pengguna_masuk_dengan_email_dan_kata_sandi_benar(): void
    {
        $pengguna = Pengguna::factory()->create(['email_kampus' => 'a@b.test']);

        $this->post('/login', ['email' => 'a@b.test', 'kata_sandi' => 'password'])->assertRedirect('/pindai');
        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_admin_diarahkan_ke_verifikasi_setelah_masuk(): void
    {
        $admin = Pengguna::factory()->admin()->create(['email_kampus' => 'admin@b.test']);

        $this->post('/login', ['email' => 'admin@b.test', 'kata_sandi' => 'password'])->assertRedirect('/admin/verifikasi');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_setelah_masuk_kembali_ke_halaman_yang_tadi_diminta(): void
    {
        Pengguna::factory()->create(['email_kampus' => 'a@b.test']);

        $this->get('/aktivitas')->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'a@b.test', 'kata_sandi' => 'password'])->assertRedirect(url('/aktivitas'));
    }

    public function test_kata_sandi_salah_ditolak_dengan_pesan(): void
    {
        Pengguna::factory()->create(['email_kampus' => 'a@b.test']);

        $this->from('/login')->post('/login', ['email' => 'a@b.test', 'kata_sandi' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_email_dan_kata_sandi_wajib_diisi(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'kata_sandi']);
        $this->assertGuest();
    }

    public function test_akun_tidak_aktif_tidak_bisa_masuk(): void
    {
        Pengguna::factory()->create(['email_kampus' => 'a@b.test', 'status_akun' => 'nonaktif']);

        $this->from('/login')->post('/login', ['email' => 'a@b.test', 'kata_sandi' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pengguna_yang_sudah_masuk_tidak_melihat_halaman_login_lagi(): void
    {
        $this->actingAs(Pengguna::factory()->create())->get('/login')->assertRedirect();
    }

    public function test_keluar_mengakhiri_sesi_dan_kembali_ke_halaman_login(): void
    {
        $this->actingAs(Pengguna::factory()->create())->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/pindai')->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_bisa_memanggil_keluar(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_tombol_dev_dan_route_dev_login_tidak_ada_di_luar_env_local(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Masuk sebagai User (dev)')
            ->assertDontSee('Masuk sebagai Admin (dev)');
        $this->get('/dev-login/user@pilahku.test')->assertNotFound();
        $this->assertGuest();
    }
}
