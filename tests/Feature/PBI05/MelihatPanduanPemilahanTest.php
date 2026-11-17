<?php

namespace Tests\Feature\PBI05;

use App\Models\KategoriSampah;
use App\Models\PanduanPemilahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SCRUM-28 · PBI-5 Melihat Panduan Pemilahan Sampah
 *
 * Use Case  : Melihat Panduan Pemilahan Sampah
 * Aktor     : User
 * Awal      : Pengguna membuka website PilahKu dan mengakses halaman panduan pemilahan
 * Akhir     : Panduan pemilahan sampah ditampilkan kepada pengguna
 */
class MelihatPanduanPemilahanTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(string $nama = 'Plastik', string $kode = 'PLS', string $status = 'aktif'): KategoriSampah
    {
        return KategoriSampah::create(['nama_kategori' => $nama, 'kode' => $kode, 'warna_label' => '#3b82f6', 'status' => $status]);
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

    // ------------------------------------------------------------------ Test Case

    /** TC-PND-01 · Membuka menu Panduan Pemilahan → daftar panduan per kategori tampil. */
    public function test_tc_pnd_01_mengakses_menu_panduan_menampilkan_daftar_per_kategori(): void
    {
        $plastik = $this->kategori('Plastik', 'PLS');
        $organik = $this->kategori('Organik', 'ORG');
        $this->panduan($plastik, 'Memilah botol plastik', 'memilah-botol-plastik', 'Bilas lalu remas botol.');
        $this->panduan($organik, 'Memilah sisa makanan', 'memilah-sisa-makanan', 'Tiriskan sisa makanan.');

        $this->get(route('home'))->assertOk()->assertSee(route('panduan.index'));

        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee('Panduan Pemilahan Sampah')
            ->assertSee('Plastik')
            ->assertSee('Organik')
            ->assertSee('Ringkasan Memilah botol plastik')
            ->assertSee('Ringkasan Memilah sisa makanan');
    }

    /** TC-PND-02 · Klik panduan "Plastik" → isi panduan Plastik tampil. */
    public function test_tc_pnd_02_membuka_salah_satu_topik_panduan(): void
    {
        $plastik = $this->kategori('Plastik', 'PLS');
        $this->panduan($plastik, 'Memilah botol plastik', 'memilah-botol-plastik', 'Bilas botol, lepas label, lalu remas.');
        $this->panduan($this->kategori('Organik', 'ORG'), 'Memilah sisa makanan', 'memilah-sisa-makanan', 'Tiriskan sisa makanan.');

        $this->get(route('panduan.kategori', $plastik->id_kategori))
            ->assertOk()
            ->assertSee('Plastik')
            ->assertSee('Bilas botol, lepas label, lalu remas.')
            ->assertDontSee('Tiriskan sisa makanan.');

        $this->get(route('panduan.show', 'memilah-botol-plastik'))
            ->assertOk()
            ->assertSee('Memilah botol plastik')
            ->assertSee('Bilas botol, lepas label, lalu remas.');
    }

    /** TC-PND-03 · Koneksi database gagal → pesan kesalahan + opsi muat ulang. */
    public function test_tc_pnd_03_database_gangguan_saat_halaman_diakses(): void
    {
        Schema::drop('panduan_pemilahan');
        Schema::drop('kategori_sampah');

        $this->get(route('panduan.index'))
            ->assertStatus(503)
            ->assertSee('Data kategori belum dapat dimuat')
            ->assertSee('Muat Ulang')
            ->assertSee('href="'.route('panduan.index').'"', false);
    }

    /** TC-PND-04 · Tabel panduan kosong → pesan "Panduan belum tersedia". */
    public function test_tc_pnd_04_belum_ada_data_panduan(): void
    {
        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee('Panduan belum tersedia')
            ->assertSee('Muat Ulang');
    }

    // ------------------------------------------------------------------ Use Case

    /** Skenario Normal: buka menu Panduan → pilih topik → isi panduan tampil. */
    public function test_use_case_skenario_normal(): void
    {
        $plastik = $this->kategori('Plastik', 'PLS');
        $this->panduan($plastik, 'Memilah botol plastik', 'memilah-botol-plastik', 'Bilas botol, lepas label, lalu remas.');

        // 1–2. Membuka menu Panduan → daftar panduan tampil, menaut ke topik.
        $this->get(route('panduan.index'))
            ->assertOk()
            ->assertSee(route('panduan.kategori', $plastik->id_kategori));

        // 3–4. Memilih topik → isi panduan tampil.
        $this->get(route('panduan.kategori', $plastik->id_kategori))
            ->assertOk()
            ->assertSee('Bilas botol, lepas label, lalu remas.');
    }

    /** Skenario Gagal: data kosong atau kategori nonaktif → pesan informasi yang jelas. */
    public function test_use_case_skenario_gagal(): void
    {
        $nonaktif = $this->kategori('Kaca', 'KCA', 'nonaktif');
        $this->panduan($nonaktif, 'Memilah kaca', 'memilah-kaca', 'Bungkus pecahan kaca.');

        // Hanya kategori berstatus Aktif yang ditampilkan, jadi daftar dianggap kosong.
        $this->get(route('panduan.index'))->assertOk()->assertSee('Panduan belum tersedia')->assertDontSee('Memilah kaca');
        $this->get(route('panduan.kategori', $nonaktif->id_kategori))->assertNotFound();
        $this->get(route('panduan.show', 'memilah-kaca'))->assertNotFound();
    }
}
