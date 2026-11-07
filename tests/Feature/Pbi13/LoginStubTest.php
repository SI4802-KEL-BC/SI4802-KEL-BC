<?php

namespace Tests\Feature\Pbi13;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** STUB PBI-13: halaman login tampilan saja (login asli = PBI-01). */
class LoginStubTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_menampilkan_form_tanpa_kerangka_sidebar(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('PilahKu')
            ->assertSee('Email Kampus')
            ->assertSee('Kata Sandi')
            ->assertSee('Masuk')
            ->assertSee('Daftar')
            ->assertSee('Login belum tersedia (PBI-01)')
            ->assertDontSee('id="sidebar"', false) // tidak memakai sidebar
            ->assertDontSee('Verifikasi Manual');
    }

    public function test_route_login_tetap_bernama_login_untuk_redirect_middleware_auth(): void
    {
        $this->assertSame(url('/login'), route('login'));
        $this->get('/pindai')->assertRedirect(route('login'));
    }

    public function test_form_login_tidak_memproses_apa_pun(): void
    {
        $pengguna = Pengguna::factory()->create(['email_kampus' => 'a@b.test']);

        // Tidak ada route POST /login: permintaan ditolak 405 dan tidak ada yang login.
        $this->post('/login', ['email' => 'a@b.test', 'kata_sandi' => 'password'])->assertStatus(405);
        $this->assertGuest();
        $this->assertModelExists($pengguna);
    }

    public function test_tombol_dev_dan_route_dev_login_tidak_ada_di_luar_env_local(): void
    {
        $this->withoutVite();

        $this->assertSame('testing', app()->environment());
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Masuk sebagai User (dev)')
            ->assertDontSee('Masuk sebagai Admin (dev)');
        $this->get('/dev-login/user@pilahku.test')->assertNotFound();
        $this->assertGuest();
    }
}
