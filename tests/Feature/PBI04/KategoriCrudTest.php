<?php

namespace Tests\Feature\PBI04;

use App\Models\KategoriSampah;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SCRUM-27 · PBI-4 CRUD Kategori Sampah
 *
 * Use Case  : Mengelola Kategori Sampah
 * Aktor     : Admin
 * Awal      : Admin telah login dan membuka halaman pengelolaan kategori sampah
 * Akhir     : Data kategori sampah berhasil dikelola dan diperbarui
 */
class KategoriCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Kelola kategori hanya untuk Admin.
        $this->actingAs(Pengguna::factory()->admin()->create());
    }

    /** TC-KAT-06 · Nama "Plastik" (atau kodenya) sudah ada → ditolak, pesan sudah digunakan. */
    public function test_tc_kat_06_menambah_kategori_dengan_nama_atau_kode_duplikat(): void
    {
        KategoriSampah::create(['nama_kategori' => 'Plastik', 'kode' => 'PLS', 'status' => 'aktif']);

        $this->post(route('kategori.store'), ['nama_kategori' => 'Plastik', 'kode' => 'PLS2'])
            ->assertSessionHasErrors(['nama_kategori' => 'Nama kategori sudah digunakan']);

        $this->post(route('kategori.store'), ['nama_kategori' => 'Plastik Keras', 'kode' => 'PLS'])
            ->assertSessionHasErrors(['kode' => 'Kode kategori sudah digunakan']);

        $this->assertDatabaseCount('kategori_sampah', 1);
    }

    /** TC-KAT-07 · User biasa / belum login membuka halaman → 403 / redirect login. */
    public function test_tc_kat_07_non_admin_mengakses_halaman_kategori(): void
    {
        auth()->logout();
        $this->get(route('kategori.index'))->assertRedirect(route('login'));
        $this->post(route('kategori.store'), ['nama_kategori' => 'X', 'kode' => 'X'])->assertRedirect(route('login'));

        $this->actingAs(Pengguna::factory()->create())->get(route('kategori.index'))->assertForbidden();
        $this->post(route('kategori.store'), ['nama_kategori' => 'X', 'kode' => 'X'])->assertForbidden();
        $this->assertDatabaseCount('kategori_sampah', 0);
    }

    /** Skenario Normal: buka menu → tambah → ubah → nonaktifkan. */
    public function test_use_case_skenario_normal(): void
    {
        // 1–2. Membuka menu kategori → daftar kategori tampil.
        $this->get(route('kategori.index'))->assertOk()->assertSee('Kelola Kategori Sampah');

        // 3–4. Mengisi form tambah dan klik Simpan → divalidasi dan disimpan.
        $this->post(route('kategori.store'), [
            'nama_kategori' => 'Plastik', 'kode' => 'PLS', 'warna_label' => 'bg-yellow-400', 'deskripsi' => 'Sampah plastik',
        ])->assertSessionHas('status', 'Kategori berhasil ditambahkan.');
        $kategori = KategoriSampah::where('kode', 'PLS')->firstOrFail();
        $this->assertSame('aktif', $kategori->status);

        // 3–4. Mengisi form ubah dan klik Simpan.
        $this->put(route('kategori.update', $kategori), [
            'nama_kategori' => 'Plastik', 'kode' => 'PLS', 'deskripsi' => 'Botol dan kemasan plastik',
        ])->assertSessionHas('status', 'Kategori berhasil diperbarui.');
        $this->assertSame('Botol dan kemasan plastik', $kategori->fresh()->deskripsi);

        // 5–6. Klik Nonaktifkan dan konfirmasi → status Nonaktif, data tetap ada.
        $this->post(route('kategori.toggle', $kategori))->assertSessionHas('status', 'Status kategori berhasil diperbarui.');
        $this->assertSame('nonaktif', $kategori->fresh()->status);
        $this->assertModelExists($kategori);
    }

    /** Skenario Gagal: menyimpan form dengan field wajib kosong → ditolak dengan pesan. */
    public function test_use_case_skenario_gagal(): void
    {
        $this->from(route('kategori.index'))->followingRedirects()
            ->post(route('kategori.store'), ['nama_kategori' => '', 'kode' => ''])
            ->assertOk()
            ->assertSee('Nama kategori wajib diisi')
            ->assertSee('Kode kategori wajib diisi');

        $this->assertDatabaseCount('kategori_sampah', 0);
    }

    public function test_tc_kat_01_menampilkan_daftar_kategori(): void
    {
        KategoriSampah::create([
            'nama_kategori' => 'Plastik',
            'kode' => 'PLS',
            'deskripsi' => 'Sampah plastik',
            'cara_penanganan' => 'Pisahkan untuk didaur ulang',
            'status' => 'aktif',
        ]);

        $this->get(route('kategori.index'))
            ->assertOk()
            ->assertSee('Plastik')
            ->assertSee('Sampah plastik')
            ->assertSee('Pisahkan untuk didaur ulang')
            ->assertSee('Aktif');
    }

    public function test_tc_kat_02_menambah_kategori_dengan_data_valid(): void
    {
        $this->post(route('kategori.store'), [
            'nama_kategori' => 'Organik',
            'kode' => 'ORG',
            'deskripsi' => 'Sampah mudah terurai',
            'cara_penanganan' => 'Olah menjadi kompos',
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kategori berhasil ditambahkan.');

        $this->assertDatabaseHas('kategori_sampah', [
            'nama_kategori' => 'Organik',
            'kode' => 'ORG',
            'cara_penanganan' => 'Olah menjadi kompos',
            'status' => 'aktif',
        ]);
    }

    public function test_tc_kat_03_menolak_kategori_dengan_field_wajib_kosong(): void
    {
        $this->from(route('kategori.index'))
            ->post(route('kategori.store'), [])
            ->assertRedirect(route('kategori.index'))
            ->assertSessionHasErrors(['nama_kategori' => 'Nama kategori wajib diisi', 'kode']);

        $this->assertDatabaseCount('kategori_sampah', 0);
    }

    public function test_tc_kat_04_mengubah_kategori_dengan_data_valid(): void
    {
        $kategori = KategoriSampah::create([
            'nama_kategori' => 'Kertas',
            'kode' => 'KRT',
            'deskripsi' => 'Deskripsi lama',
            'cara_penanganan' => 'Cara lama',
            'status' => 'nonaktif',
        ]);

        $this->put(route('kategori.update', $kategori), [
            'nama_kategori' => 'Kertas dan Kardus',
            'kode' => 'KRT',
            'deskripsi' => 'Kertas bersih dan kering',
            'cara_penanganan' => 'Kumpulkan untuk didaur ulang',
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kategori berhasil diperbarui.');

        $this->assertDatabaseHas('kategori_sampah', [
            'id_kategori' => $kategori->id_kategori,
            'nama_kategori' => 'Kertas dan Kardus',
            'deskripsi' => 'Kertas bersih dan kering',
            'cara_penanganan' => 'Kumpulkan untuk didaur ulang',
            'status' => 'nonaktif',
        ]);
    }

    public function test_tc_kat_05_menonaktifkan_kategori_setelah_konfirmasi(): void
    {
        $kategori = KategoriSampah::create([
            'nama_kategori' => 'Logam',
            'kode' => 'LOG',
            'status' => 'aktif',
        ]);

        $this->get(route('kategori.index'))
            ->assertOk()
            ->assertSee("confirm('Nonaktifkan kategori ini?')", false);

        // POST toggle mewakili aksi admin setelah menyetujui dialog konfirmasi browser.
        $this->post(route('kategori.toggle', $kategori))
            ->assertRedirect()
            ->assertSessionHas('status', 'Status kategori berhasil diperbarui.');

        $this->assertDatabaseHas('kategori_sampah', [
            'id_kategori' => $kategori->id_kategori,
            'status' => 'nonaktif',
        ]);
    }
}
