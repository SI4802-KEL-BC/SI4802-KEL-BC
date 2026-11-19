<?php

namespace Tests\Browser\PBI05;

use App\Models\KategoriSampah;
use App\Models\PanduanPemilahan;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/** PBI-05: melihat panduan pemilahan melalui GUI browser. */
class PanduanPemilahanTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_tc_pnd_01_menu_panduan_menampilkan_daftar_kategori(): void
    {
        $plastik = $this->kategori('Plastik', 'PLS');
        $organik = $this->kategori('Organik', 'ORG');
        $this->panduan($plastik, 'Memilah botol plastik', 'memilah-botol-plastik', 'Bilas lalu remas botol.');
        $this->panduan($organik, 'Memilah sisa makanan', 'memilah-sisa-makanan', 'Tiriskan sisa makanan.');

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->clickLink('Panduan')
                ->waitForLocation('/panduan')
                ->assertSee('Panduan Pemilahan Sampah')
                ->assertSee('Plastik')
                ->assertSee('Organik')
                ->assertSee('Ringkasan Memilah botol plastik')
                ->assertSee('Ringkasan Memilah sisa makanan');
        });
    }

    public function test_tc_pnd_02_pengguna_membuka_detail_instruksi_kategori(): void
    {
        $plastik = $this->kategori('Plastik', 'PLS');
        $this->panduan($plastik, 'Memilah botol plastik', 'memilah-botol-plastik', 'Bilas botol, lepas label, lalu remas.');
        $this->panduan($this->kategori('Organik', 'ORG'), 'Memilah sisa makanan', 'memilah-sisa-makanan', 'Tiriskan sisa makanan.');

        $this->browse(function (Browser $browser) use ($plastik) {
            $browser->visit('/panduan')
                ->with('[data-guide-card][data-search*="plastik"]', function (Browser $card) {
                    $card->clickLink('Lihat detail kategori');
                })
                ->waitForLocation('/panduan/kategori/'.$plastik->id_kategori)
                ->assertSee('Plastik')
                ->assertSee('Bilas botol, lepas label, lalu remas.')
                ->assertDontSee('Tiriskan sisa makanan.');
        });
    }

    public function test_daftar_tanpa_data_menampilkan_pesan(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/panduan')
                ->assertSee('Panduan belum tersedia')
                ->assertSee('Kategori akan tampil di sini');
        });
    }

    public function test_kategori_aktif_tetap_memiliki_detail_tanpa_data_panduan(): void
    {
        $kategori = $this->kategori('Plastik', 'PLS');
        $kategori->update([
            'deskripsi' => 'Kemasan dan barang berbahan plastik.',
            'karakteristik' => 'Botol minum, gelas plastik, sedotan',
            'cara_penanganan' => 'Kosongkan isi, bilas, lalu keringkan.',
        ]);

        $this->browse(function (Browser $browser) use ($kategori) {
            $browser->visit('/panduan')
                ->assertSee('Plastik')
                ->assertSee('Botol minum')
                ->with('[data-guide-card][data-search*="plastik"]', function (Browser $card) {
                    $card->clickLink('Lihat detail kategori');
                })
                ->waitForLocation('/panduan/kategori/'.$kategori->id_kategori)
                ->assertSee('Kosongkan isi, bilas, lalu keringkan.')
                ->assertSee('Botol minum');
        });
    }

    public function test_kategori_nonaktif_tidak_muncul_di_panduan(): void
    {
        $this->kategori('Plastik', 'PLS');
        $this->kategori('Kaca', 'KCA', 'nonaktif');

        $this->browse(function (Browser $browser) {
            $browser->visit('/panduan')
                ->assertSee('Plastik')
                ->assertDontSee('Kaca');
        });
    }

    public function test_pencarian_kategori_dapat_dilakukan_dari_halaman_panduan(): void
    {
        $this->kategori('Plastik', 'PLS');
        $this->kategori('Organik', 'ORG');

        $this->browse(function (Browser $browser) {
            $browser->visit('/panduan')
                ->type('input[type=search]', 'plastik')
                ->assertScript("document.querySelector('[data-guide-card][data-search*=plastik]').style.display !== 'none'")
                ->assertScript("document.querySelector('[data-guide-card][data-search*=organik]').style.display === 'none'");
        });
    }

    public function test_pengguna_membuka_detail_instruksi_panduan(): void
    {
        $kategori = $this->kategori('Plastik', 'PLS');
        $this->panduan($kategori, 'Memilah botol plastik', 'memilah-botol-plastik', 'Bilas botol lalu remas.');

        $this->browse(function (Browser $browser) {
            $browser->visit('/panduan/memilah-botol-plastik')
                ->assertSee('Memilah botol plastik')
                ->assertSee('Bilas botol lalu remas.');
        });
    }

    private function kategori(string $nama, string $kode, string $status = 'aktif'): KategoriSampah
    {
        return KategoriSampah::create([
            'nama_kategori' => $nama,
            'kode' => $kode,
            'warna_label' => '#3b82f6',
            'status' => $status,
        ]);
    }

    private function panduan(KategoriSampah $kategori, string $judul, string $slug, string $langkah): PanduanPemilahan
    {
        return PanduanPemilahan::create([
            'id_kategori' => $kategori->id_kategori,
            'judul' => $judul,
            'slug' => $slug,
            'ringkasan' => 'Ringkasan '.$judul,
            'langkah_pemilahan' => $langkah,
            'status' => 'aktif',
        ]);
    }
}
