<?php

namespace Tests\Browser\PBI15;

use App\Models\AktivitasPemilahan;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/** PBI-15 (SCRUM-38) · Status verifikasi aktivitas lewat browser. */
class StatusVerifikasiTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_terverifikasi_menampilkan_poin(): void
    {
        $pengguna = $this->pengguna();
        $aktivitas = $this->aktivitas($pengguna, AktivitasPemilahan::TERVERIFIKASI);

        $this->browse(function (Browser $browser) use ($pengguna, $aktivitas) {
            $browser->loginAs($pengguna)
                ->visit('/aktivitas/'.$aktivitas->id_aktivitas)
                ->assertSee('Terverifikasi')
                ->assertSee('+10')
                ->assertDontSee('Ubah Catatan');
        });
    }

    public function test_ditolak_menampilkan_alasan_tanpa_poin(): void
    {
        $pengguna = $this->pengguna();
        $aktivitas = $this->aktivitas($pengguna, AktivitasPemilahan::DITOLAK);

        $this->browse(function (Browser $browser) use ($pengguna, $aktivitas) {
            $browser->loginAs($pengguna)
                ->visit('/aktivitas/'.$aktivitas->id_aktivitas)
                ->assertSee('Ditolak')
                ->assertSee('Alasan penolakan')
                ->assertSee('Kategori tidak sesuai tempat sampah')
                ->assertSee('Tanpa poin');
        });
    }

    public function test_menunggu_berubah_otomatis_setelah_admin_menyetujui(): void
    {
        $pengguna = $this->pengguna();
        $aktivitas = $this->aktivitas($pengguna);
        $admin = $this->admin();

        $this->browse(function (Browser $penggunaBrowser, Browser $adminBrowser) use ($pengguna, $aktivitas, $admin) {
            // Jendela 1: pengguna membuka detail yang masih menunggu.
            $penggunaBrowser->loginAs($pengguna)
                ->visit('/aktivitas/'.$aktivitas->id_aktivitas)
                ->assertSee('Menunggu Validasi')
                ->assertSee('Status diperiksa otomatis setiap 10 detik.');

            // Jendela 2: admin menyetujui.
            $adminBrowser->loginAs($admin)
                ->visit('/admin/verifikasi/'.$aktivitas->id_aktivitas)
                ->press('Setujui')
                ->waitForText('Aktivitas disetujui.');

            // Halaman pengguna memuat ulang sendiri tanpa disentuh (pemeriksaan tiap 10 detik).
            $penggunaBrowser->waitForText('Hasil: Terverifikasi', 25)
                ->assertDontSee('Status diperiksa otomatis setiap 10 detik.');
        });
    }

    public function test_pengguna_lain_tidak_bisa_melihat_status(): void
    {
        $aktivitas = $this->aktivitas($this->pengguna());

        $this->browse(function (Browser $browser) use ($aktivitas) {
            $browser->loginAs($this->pengguna(['nama_lengkap' => 'Orang Lain']))
                ->visit('/aktivitas/'.$aktivitas->id_aktivitas)
                ->assertDontSee('Detail Aktivitas '.$this->kodeAktivitas($aktivitas));
        });
    }
}
