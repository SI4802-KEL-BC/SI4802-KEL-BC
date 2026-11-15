<?php

namespace Tests\Feature;

use App\Models\KategoriSampah;
use App\Models\PanduanPemilahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PanduanPemilahanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_dapat_membuka_menu_panduan_dari_beranda(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('panduan.index'));
    }

    public function test_daftar_hanya_menampilkan_panduan_aktif_dengan_kategori_aktif(): void
    {
        $kategori = $this->buatKategori('Plastik', 'aktif');
        $panduan = $this->buatPanduan($kategori, 'Panduan memilah plastik', 'panduan-plastik');

        $kategoriNonaktif = $this->buatKategori('Kertas', 'nonaktif', 'KRT');
        $this->buatPanduan($kategoriNonaktif, 'Panduan kertas', 'panduan-kertas');

        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee($panduan->ringkasan)
            ->assertSee('Plastik')
            ->assertDontSee('Kode KRT');
    }

    public function test_kategori_pbi_4_langsung_tampil_dan_detailnya_dapat_dibuka_tanpa_seed_panduan(): void
    {
        $kategori = $this->buatKategori('Plastik', 'aktif');
        $kategori->update([
            'deskripsi' => 'Kemasan dan barang berbahan plastik.',
            'cara_penanganan' => 'Kosongkan isi, bilas, lalu keringkan.',
            'karakteristik' => 'Botol minum, gelas plastik, sedotan',
        ]);

        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee('Plastik')
            ->assertSee('Botol minum')
            ->assertSee(route('panduan.kategori', $kategori->id_kategori));

        $this->get(route('panduan.kategori', $kategori->id_kategori))
            ->assertOk()
            ->assertSee('Kosongkan isi, bilas, lalu keringkan.')
            ->assertSee('Botol minum');
    }

    public function test_pengguna_dapat_membaca_detail_instruksi_pemilahan(): void
    {
        $kategori = $this->buatKategori('Organik', 'aktif');
        $panduan = $this->buatPanduan(
            $kategori,
            'Panduan memilah organik',
            'panduan-organik',
            "Pisahkan sisa makanan.\nGunakan wadah khusus organik.",
        );

        $this->get(route('panduan.show', $panduan->slug))
            ->assertOk()
            ->assertSee($panduan->judul)
            ->assertSee('Organik')
            ->assertSee('Pisahkan sisa makanan.')
            ->assertSee('Gunakan wadah khusus organik.');
    }

    public function test_daftar_menampilkan_pesan_informatif_ketika_data_kosong(): void
    {
        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee('Belum ada kategori sampah aktif')
            ->assertSee('Kategori akan tampil di sini');
    }

    public function test_daftar_menampilkan_pesan_ketika_database_tidak_dapat_diakses(): void
    {
        Schema::drop('kategori_sampah');

        $this->get(route('panduan.index'))
            ->assertStatus(503)
            ->assertSee('Data kategori belum dapat dimuat')
            ->assertSee('Periksa koneksi');
    }

    public function test_daftar_tetap_menampilkan_kategori_jika_tabel_panduan_belum_dimigrasikan(): void
    {
        $this->buatKategori('Organik', 'aktif');
        Schema::drop('panduan_pemilahan');

        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee('Organik')
            ->assertSee('Jalankan migrasi PBI-5');
    }

    private function buatKategori(string $nama, string $status, string $kode = 'PLS'): KategoriSampah
    {
        return KategoriSampah::create([
            'nama_kategori' => $nama,
            'kode' => $kode,
            'status' => $status,
        ]);
    }

    private function buatPanduan(
        KategoriSampah $kategori,
        string $judul,
        string $slug,
        string $langkah = 'Pisahkan sampah sesuai kategori.',
    ): PanduanPemilahan {
        return PanduanPemilahan::create([
            'id_kategori' => $kategori->id_kategori,
            'judul' => $judul,
            'slug' => $slug,
            'ringkasan' => 'Ringkasan panduan.',
            'langkah_pemilahan' => $langkah,
            'status' => 'aktif',
        ]);
    }
}
