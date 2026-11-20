<?php

namespace Tests\Browser\PBI04;

use App\Models\KategoriSampah;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/** PBI-04: CRUD kategori sampah melalui GUI browser. */
class KategoriCrudTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_tc_kat_01_admin_melihat_daftar_kategori(): void
    {
        KategoriSampah::create(['nama_kategori' => 'Plastik', 'kode' => 'PLS', 'status' => 'aktif']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->assertSee('Kelola Kategori Sampah')
                ->assertSee('Plastik')
                ->assertSee('PLS');
        });
    }

    public function test_tc_kat_02_admin_menambahkan_kategori_dari_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->type('nama_kategori', 'Organik')
                ->type('kode', 'ORG')
                ->type('deskripsi', 'Sampah mudah terurai')
                ->type('cara_penanganan', 'Olah menjadi kompos')
                ->press('Simpan Kategori')
                ->waitForText('Kategori berhasil ditambahkan.')
                ->assertSee('Organik')
                ->assertSee('ORG');
        });

        $this->assertDatabaseHas('kategori_sampah', ['nama_kategori' => 'Organik', 'kode' => 'ORG', 'status' => 'aktif']);
    }

    public function test_tc_kat_03_form_menampilkan_validasi_data_wajib(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->script("document.querySelector('[name=nama_kategori]').removeAttribute('required'); document.querySelector('[name=kode]').removeAttribute('required');");

            $browser->press('Simpan Kategori')
                ->waitForText('Nama kategori wajib diisi')
                ->assertSee('Kode kategori wajib diisi');
        });
    }

    public function test_tc_kat_04_admin_mengubah_kategori_dari_tombol_edit(): void
    {
        KategoriSampah::create([
            'nama_kategori' => 'Kertas',
            'kode' => 'KRT',
            'deskripsi' => 'Deskripsi lama',
            'status' => 'nonaktif',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->with('tbody tr:nth-child(1)', function (Browser $row) {
                    $row->press('Edit');
                })
                ->waitForText('Edit Kategori: Kertas')
                ->type('nama_kategori', 'Kertas dan Kardus')
                ->type('deskripsi', 'Kertas bersih dan kering')
                ->press('Simpan Kategori')
                ->waitForText('Kategori berhasil diperbarui.')
                ->assertSee('Kertas dan Kardus');
        });

        $this->assertDatabaseHas('kategori_sampah', [
            'kode' => 'KRT',
            'nama_kategori' => 'Kertas dan Kardus',
            'deskripsi' => 'Kertas bersih dan kering',
            'status' => 'nonaktif',
        ]);
    }

    public function test_tc_kat_05_admin_menonaktifkan_kategori_dengan_konfirmasi(): void
    {
        KategoriSampah::create(['nama_kategori' => 'Logam', 'kode' => 'LOG', 'status' => 'aktif']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->with('tbody tr:nth-child(1)', function (Browser $row) {
                    $row->press('Nonaktifkan');
                })
                ->waitForDialog()
                ->acceptDialog()
                ->waitForText('Status kategori berhasil diperbarui.')
                ->assertSee('Nonaktif');
        });

        $this->assertDatabaseHas('kategori_sampah', ['kode' => 'LOG', 'status' => 'nonaktif']);
    }

    public function test_tc_kat_06_duplikasi_nama_kategori_ditolak_di_gui(): void
    {
        KategoriSampah::create(['nama_kategori' => 'Plastik', 'kode' => 'PLS', 'status' => 'aktif']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->type('nama_kategori', 'Plastik')
                ->type('kode', 'PLS2')
                ->press('Simpan Kategori')
                ->waitForText('Nama kategori sudah digunakan');
        });
    }

    public function test_tc_kat_06_kode_kategori_duplikat_ditolak_di_gui(): void
    {
        KategoriSampah::create(['nama_kategori' => 'Plastik', 'kode' => 'PLS', 'status' => 'aktif']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin())
                ->visit('/kategori')
                ->type('nama_kategori', 'Plastik Keras')
                ->type('kode', 'PLS')
                ->press('Simpan Kategori')
                ->waitForText('Kode kategori sudah digunakan');
        });

        $this->assertDatabaseCount('kategori_sampah', 1);
    }

    public function test_tc_kat_07_user_biasa_tidak_dapat_membuka_halaman_kategori(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/kategori')
                ->assertPathIs('/login')
                ->loginAs($this->pengguna())
                ->visit('/kategori')
                ->assertSee('403');
        });
    }
}
