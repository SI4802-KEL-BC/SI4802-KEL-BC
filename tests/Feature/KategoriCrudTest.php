<?php

namespace Tests\Feature;

use App\Models\KategoriSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriCrudTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSessionHasErrors(['nama_kategori', 'kode']);

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
