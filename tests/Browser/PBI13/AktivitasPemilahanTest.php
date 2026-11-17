<?php

namespace Tests\Browser\PBI13;

use App\Models\AktivitasPemilahan;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/** PBI-13 (SCRUM-36) · Catat, ubah, batalkan, dan verifikasi aktivitas pemilahan lewat browser. */
class AktivitasPemilahanTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_pindai_kode_tong_lalu_foto_dari_tong_tercatat(): void
    {
        $pengguna = $this->pengguna();
        $tong = $this->tongPlastik();

        $this->browse(function (Browser $browser) use ($pengguna, $tong) {
            $browser->loginAs($pengguna)
                ->visit('/pindai')
                ->type('kode_perangkat', $tong->kode_perangkat)
                ->press('Mulai Sesi')
                ->waitForText("QR tong berhasil dipindai di {$tong->nama_lokasi}.");

            // Kamera tong mengirim foto ke API perangkat.
            $this->assertTrue($this->kirimFotoDariTong($tong->kode_perangkat)->successful());

            $aktivitas = AktivitasPemilahan::latest('id_aktivitas')->firstOrFail();

            $browser->visit('/aktivitas')
                ->assertSee($this->kodeAktivitas($aktivitas))
                ->assertSee('Terverifikasi')
                ->clickLink($this->kodeAktivitas($aktivitas))
                ->waitForText('Detail Aktivitas '.$this->kodeAktivitas($aktivitas))
                ->assertSee($tong->nama_lokasi)
                ->assertSee('+10');
        });
    }

    public function test_membuka_url_qr_tong_langsung_memulai_sesi(): void
    {
        $pengguna = $this->pengguna();
        $tong = $this->tongPlastik();

        $this->browse(function (Browser $browser) use ($pengguna, $tong) {
            $browser->loginAs($pengguna)
                ->visit('/pindai/'.$tong->kode_perangkat)
                ->assertPathIs('/pindai')
                ->assertSee("QR tong berhasil dipindai di {$tong->nama_lokasi}.");
        });
    }

    public function test_kode_tong_tidak_dikenal_menampilkan_pesan(): void
    {
        $pengguna = $this->pengguna();
        $this->tongPlastik();

        $this->browse(function (Browser $browser) use ($pengguna) {
            $browser->loginAs($pengguna)
                ->visit('/pindai')
                ->type('kode_perangkat', 'TIDAK-ADA')
                ->press('Mulai Sesi')
                ->waitForLocation('/pindai')
                ->assertDontSee('berhasil dipindai');
        });
    }

    public function test_ubah_catatan_lalu_batalkan_aktivitas_menunggu(): void
    {
        $pengguna = $this->pengguna();
        $aktivitas = $this->aktivitas($pengguna);
        $kode = $this->kodeAktivitas($aktivitas);

        $this->browse(function (Browser $browser) use ($pengguna, $aktivitas, $kode) {
            $browser->loginAs($pengguna)
                ->visit('/aktivitas/'.$aktivitas->id_aktivitas)
                ->assertSee('Detail Aktivitas '.$kode)
                ->press('Ubah Catatan')
                ->waitFor('#modal-catatan[open]')
                ->type('catatan', 'Botol plastik dari kantin')
                ->press('Simpan Catatan')
                ->waitForText('Catatan berhasil disimpan.')
                ->assertSee('Botol plastik dari kantin')
                ->press('Batalkan Aktivitas')
                ->waitFor('#modal-batal[open]')
                ->press('Ya, Batalkan')
                ->waitForLocation('/pindai')
                ->assertSee('Aktivitas berhasil dibatalkan.');
        });

        $this->assertNull(AktivitasPemilahan::find($aktivitas->id_aktivitas));
    }

    public function test_admin_menyetujui_aktivitas_dari_antrean(): void
    {
        $pengguna = $this->pengguna();
        $aktivitas = $this->aktivitas($pengguna);
        $kode = $this->kodeAktivitas($aktivitas);

        $this->browse(function (Browser $browser) use ($kode) {
            $browser->loginAs($this->admin())
                ->visit('/admin/verifikasi')
                ->assertSee('Verifikasi Manual')
                ->clickLink($kode)
                ->waitForText('Tinjau Aktivitas '.$kode)
                ->press('Setujui')
                ->waitForText('Aktivitas disetujui.')
                ->assertSee('Antrean kosong');
        });

        $this->assertSame(AktivitasPemilahan::TERVERIFIKASI, $aktivitas->fresh()->status_verifikasi);
    }

    public function test_admin_menolak_aktivitas_dengan_alasan(): void
    {
        $pengguna = $this->pengguna();
        $aktivitas = $this->aktivitas($pengguna);
        $kode = $this->kodeAktivitas($aktivitas);

        $this->browse(function (Browser $browser) use ($aktivitas, $kode) {
            $browser->loginAs($this->admin())
                ->visit('/admin/verifikasi/'.$aktivitas->id_aktivitas)
                ->assertSee('Tinjau Aktivitas '.$kode)
                ->type('alasan', 'Foto tidak jelas')
                ->press('Tolak')
                ->waitForText('Aktivitas ditolak.');
        });

        $aktivitas->refresh();
        $this->assertSame(AktivitasPemilahan::DITOLAK, $aktivitas->status_verifikasi);
        $this->assertSame('Foto tidak jelas', $aktivitas->alasan_penolakan);
    }

    public function test_admin_melihat_qr_tong_untuk_dicetak(): void
    {
        $tong = $this->tongPlastik();

        $this->browse(function (Browser $browser) use ($tong) {
            $browser->loginAs($this->admin())
                ->visit('/admin/qr-tong')
                ->assertSee('QR Tong Sampah')
                ->assertSee($tong->kode_perangkat)
                ->assertSee('/pindai/'.$tong->kode_perangkat)
                ->assertPresent('[aria-label="QR tong '.$tong->kode_perangkat.'"] svg');
        });
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->pengguna())
                ->visit('/admin/verifikasi')
                ->assertSee('403');
        });
    }
}
