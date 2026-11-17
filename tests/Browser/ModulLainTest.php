<?php

namespace Tests\Browser;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/**
 * Uji asap (smoke test) lewat browser untuk modul teman: PBI-04, 05, 07, 08, 10.
 * Memastikan halaman terbuka, tersambung dari navigasi, dan aksi utamanya berjalan.
 */
class ModulLainTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_pbi04_admin_menambah_kategori(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->assertSee('Kelola Kategori Sampah')
                ->waitFor('input[name="nama_kategori"]')
                ->type('nama_kategori', 'Elektronik')
                ->type('kode', 'ELK')
                ->press('Simpan Kategori')
                ->waitForText('Kategori berhasil ditambahkan.')
                ->assertSee('Elektronik');
        });

        $this->assertTrue(KategoriSampah::where('kode', 'ELK')->exists());
    }

    public function test_pbi05_tamu_membaca_panduan_dari_beranda(): void
    {
        $this->kategoriPlastik();

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->clickLink('Panduan')
                ->waitForLocation('/panduan')
                ->assertSee('Panduan Pemilahan Sampah')
                ->assertSee('Plastik');
        });
    }

    public function test_pbi07_admin_membuka_kelola_jadwal_dari_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/admin/verifikasi')
                ->clickLink('Kelola Jadwal')
                ->waitForLocation('/admin/jadwal-pengangkutan')
                ->assertSee('Jadwal Pengangkutan')
                ->assertSee('Belum ada jadwal');
        });
    }

    public function test_pbi08_tamu_melihat_jadwal_publik(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->clickLink('Jadwal')
                ->waitForLocation('/jadwal-pengangkutan')
                ->assertSee('Jadwal Pengangkutan')
                ->assertSee('Tidak ada jadwal');
        });
    }

    public function test_pbi10_admin_menambah_lokasi_dan_titik_tampil_di_peta(): void
    {
        $this->tongPlastik();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/admin/lokasi')
                ->assertSee('Kelola Lokasi Tempat Sampah')
                // Leaflet menggambar penanda titik yang punya koordinat.
                ->waitFor('#peta-daftar .leaflet-interactive')
                ->clickLink('+ Tambah Lokasi')
                ->waitFor('#peta-mini')
                ->type('nama_lokasi', 'Pendopo Dusk')
                ->type('kode_titik', 'PD-1')
                ->type('area', 'Pendopo')
                ->type('latitude', '-6.975500')
                ->type('longitude', '107.630800')
                ->check('kategori[]')
                ->press('Simpan Lokasi')
                ->waitForText('Lokasi berhasil ditambahkan.')
                ->assertSee('Pendopo Dusk');
        });

        $this->assertTrue(LokasiTempatSampah::where('kode_titik', 'PD-1')->exists());
    }
}
