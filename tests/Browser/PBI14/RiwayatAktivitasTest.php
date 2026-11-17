<?php

namespace Tests\Browser\PBI14;

use App\Models\AktivitasPemilahan;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/** PBI-14 (SCRUM-37) · Riwayat aktivitas pemilahan lewat browser. */
class RiwayatAktivitasTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_riwayat_kosong_menampilkan_pesan(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->pengguna())
                ->visit('/aktivitas')
                ->assertSee('Aktivitas Saya')
                ->assertSee('Belum ada aktivitas yang tercatat');
        });
    }

    public function test_riwayat_hanya_milik_sendiri_dan_bisa_difilter_status(): void
    {
        $pengguna = $this->pengguna();
        $menunggu = $this->aktivitas($pengguna);
        $terverifikasi = $this->aktivitas($pengguna, AktivitasPemilahan::TERVERIFIKASI);
        $ditolak = $this->aktivitas($pengguna, AktivitasPemilahan::DITOLAK);
        $milikOrangLain = $this->aktivitas($this->pengguna(['nama_lengkap' => 'Orang Lain']));

        $this->browse(function (Browser $browser) use ($pengguna, $menunggu, $terverifikasi, $ditolak, $milikOrangLain) {
            $browser->loginAs($pengguna)
                ->visit('/aktivitas')
                ->assertSee($this->kodeAktivitas($menunggu))
                ->assertSee($this->kodeAktivitas($terverifikasi))
                ->assertSee($this->kodeAktivitas($ditolak))
                ->assertDontSee($this->kodeAktivitas($milikOrangLain))
                ->select('status', AktivitasPemilahan::DITOLAK)
                ->waitForReload(fn (Browser $b) => $b->press('Terapkan'))
                ->assertQueryStringHas('status', AktivitasPemilahan::DITOLAK)
                ->assertSee($this->kodeAktivitas($ditolak))
                ->assertDontSee($this->kodeAktivitas($menunggu))
                ->assertDontSee($this->kodeAktivitas($terverifikasi))
                ->clickLink('Reset filter')
                ->waitForLocation('/aktivitas')
                ->assertSee($this->kodeAktivitas($menunggu));
        });
    }

    public function test_filter_tanpa_hasil_menampilkan_pesan(): void
    {
        $pengguna = $this->pengguna();
        $this->aktivitas($pengguna);

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/aktivitas?status='.urlencode(AktivitasPemilahan::DITOLAK))
                ->assertSee('Tidak ada aktivitas sesuai filter');
        });
    }

    public function test_menu_aktivitas_saya_di_sidebar_membuka_riwayat(): void
    {
        $pengguna = $this->pengguna();

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/pindai')
                ->clickLink('Aktivitas Saya')
                ->waitForLocation('/aktivitas')
                ->assertSee('Belum ada aktivitas yang tercatat');
        });
    }
}
