<?php

namespace Tests\Browser\PBI01;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/** PBI-01 · Login, logout, dan profil lewat browser. */
class LoginTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_pengguna_masuk_dengan_email_dan_kata_sandi(): void
    {
        $pengguna = $this->pengguna(['email_kampus' => 'dusk@student.test']);

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->visit('/login')
                ->assertSee('Masuk ke akunmu')
                ->assertDontSee('(dev)')
                ->type('email', 'dusk@student.test')
                ->type('kata_sandi', self::SANDI)
                ->press('Masuk')
                ->waitForLocation('/pindai')
                ->assertSee('Pindai QR Tong Sampah')
                ->assertSee($pengguna->nama_lengkap);
        });
    }

    public function test_admin_diarahkan_ke_verifikasi_manual(): void
    {
        $this->admin(['email_kampus' => 'admin@dusk.test']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@dusk.test')
                ->type('kata_sandi', self::SANDI)
                ->press('Masuk')
                ->waitForLocation('/admin/verifikasi')
                ->assertSee('Verifikasi Manual');
        });
    }

    public function test_kata_sandi_salah_menampilkan_pesan(): void
    {
        $this->pengguna(['email_kampus' => 'dusk@student.test']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'dusk@student.test')
                ->type('kata_sandi', 'salah-sandi')
                ->press('Masuk')
                ->waitForText('Email atau kata sandi salah.')
                ->assertPathIs('/login')
                ->assertInputValue('email', 'dusk@student.test');
        });
    }

    public function test_halaman_wajib_login_mengarah_ke_login_lalu_kembali(): void
    {
        $this->pengguna(['email_kampus' => 'dusk@student.test']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/aktivitas')
                ->assertPathIs('/login')
                ->type('email', 'dusk@student.test')
                ->type('kata_sandi', self::SANDI)
                ->press('Masuk')
                ->waitForLocation('/aktivitas')
                ->assertSee('Aktivitas Saya');
        });
    }

    public function test_keluar_dari_sidebar(): void
    {
        $pengguna = $this->pengguna();

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/pindai')
                ->press('Keluar')
                ->waitForLocation('/login')
                ->assertSee('Kamu sudah keluar.')
                ->visit('/pindai')
                ->assertPathIs('/login');
        });
    }

    /** TC-P01-01 lewat form registrasi. */
    public function test_registrasi_akun_dari_halaman_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->clickLink('Daftar')
                ->waitForLocation('/register')
                ->type('nama_lengkap', 'Nadia Putri')
                ->type('nim_nip', '102022400017')
                ->type('email_kampus', 'nadia@student.test')
                ->type('program_studi', 'S1 Sistem Informasi')
                ->type('kata_sandi', 'rahasia123')
                ->type('kata_sandi_confirmation', 'rahasia123')
                ->press('Daftar')
                ->waitForLocation('/login')
                ->assertSee('Akun berhasil dibuat. Silakan masuk.')
                ->type('email', 'nadia@student.test')
                ->type('kata_sandi', 'rahasia123')
                ->press('Masuk')
                ->waitForLocation('/pindai');
        });
    }

    /** TC-P01-06: memilih Batal pada dialog konfirmasi, akun tetap aktif. */
    public function test_batal_pada_dialog_nonaktifkan_akun(): void
    {
        $pengguna = $this->pengguna();

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/profile/edit')
                ->type('konfirmasi', 'NONAKTIFKAN')
                ->type('password', self::SANDI)
                ->press('Konfirmasi Nonaktifkan')
                ->waitForDialog()
                ->dismissDialog()
                ->assertPathIs('/profile/edit');
        });

        $this->assertSame('aktif', $pengguna->fresh()->status_akun);
    }

    /** TC-P01-03: menonaktifkan akun dengan konfirmasi dan kata sandi. */
    public function test_nonaktifkan_akun_dengan_konfirmasi(): void
    {
        $pengguna = $this->pengguna();

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/profile/edit')
                ->type('konfirmasi', 'NONAKTIFKAN')
                ->type('password', self::SANDI)
                ->press('Konfirmasi Nonaktifkan')
                ->waitForDialog()
                ->acceptDialog()
                ->waitForLocation('/login')
                ->assertSee('Akun berhasil dinonaktifkan.');
        });

        $this->assertSame('nonaktif', $pengguna->fresh()->status_akun);
    }

    public function test_ubah_profil(): void
    {
        $pengguna = $this->pengguna();

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/profile/edit')
                ->type('nama_panggilan', 'Dusky')
                ->press('Simpan Perubahan')
                ->waitForLocation('/profile')
                ->assertSee('Profil berhasil diperbarui!')
                ->assertSee('Dusky');
        });
    }
}
