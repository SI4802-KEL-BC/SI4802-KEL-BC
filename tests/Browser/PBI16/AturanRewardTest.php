<?php

namespace Tests\Browser\PBI16;

use App\Models\AturanReward;
use App\Models\KategoriSampah;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\DataUji;
use Tests\DuskTestCase;

/** PBI-16 · Mengelola aturan reward point lewat browser. */
class AturanRewardTest extends DuskTestCase
{
    use DatabaseTruncation;
    use DataUji;

    public function test_admin_melihat_daftar_dan_menambah_aturan_reward(): void
    {
        $kategori = KategoriSampah::factory()->create(['nama_kategori' => 'Kertas']);
        $namaAturan = 'Reward Kertas Dusk';

        $this->browse(function (Browser $browser) use ($kategori, $namaAturan): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward')
                ->assertSee('Aturan Reward Point')
                ->assertSee('Belum ada aturan reward')
                ->visit('/admin/aturan-reward?tambah=1')
                ->assertSee('Tambah Aturan')
                ->type('nama_aturan', $namaAturan)
                ->select('id_kategori', (string) $kategori->id_kategori)
                ->type('nilai_poin', '25')
                ->select('satuan', 'per item')
                ->type('batas_per_hari', '50')
                ->type('berlaku_mulai', '2026-09-01')
                ->press('Simpan Aturan')
                ->waitForText('Aturan reward berhasil ditambahkan.')
                ->assertSee($namaAturan)
                ->assertSee('RWD-01');
        });

        $this->assertDatabaseHas('aturan_reward', [
            'nama_aturan' => $namaAturan,
            'id_kategori' => $kategori->id_kategori,
            'poin_per_satuan' => 25,
            'status' => 'aktif',
        ]);
    }

    public function test_admin_mengubah_aturan_reward(): void
    {
        $aturan = AturanReward::factory()->create();

        $this->browse(function (Browser $browser) use ($aturan): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward?edit='.$aturan->id_aturan)
                ->assertSee('Edit Aturan: '.$aturan->kode_aturan)
                ->type('nama_aturan', 'Reward Revisi Dusk')
                ->click('label:has(input[value="Pengurangan"])')
                ->type('nilai_poin', '15')
                ->select('satuan', 'per kg')
                ->press('Simpan Aturan')
                ->waitForText('Aturan reward berhasil diperbarui.')
                ->assertSee('Reward Revisi Dusk');
        });

        $this->assertSame('Reward Revisi Dusk', $aturan->fresh()->nama_aturan);
        $this->assertSame('per kg', $aturan->fresh()->satuan);
    }

    public function test_admin_menonaktifkan_aturan_reward(): void
    {
        $aturan = AturanReward::factory()->create();

        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward')
                ->press('Nonaktifkan')
                ->waitForText('Status aturan reward berhasil diperbarui.')
                ->assertSee('Nonaktif')
                ->assertSee('Aktifkan');
        });

        $this->assertSame('nonaktif', $aturan->fresh()->status);
    }

    public function test_admin_mendapat_pesan_saat_nilai_poin_tidak_valid(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward?tambah=1')
                ->type('nama_aturan', 'Reward Tidak Valid')
                ->type('nilai_poin', '0')
                ->script("document.querySelector('input[name=nilai_poin]').removeAttribute('min')");

            $browser->press('Simpan Aturan')
                ->waitForText('Nilai poin harus lebih besar dari 0.')
                ->assertSee('Tambah Aturan');
        });

        $this->assertDatabaseMissing('aturan_reward', ['nama_aturan' => 'Reward Tidak Valid']);
    }

    public function test_admin_melihat_aturan_yang_sudah_ada(): void
    {
        $aturan = AturanReward::factory()->create([
            'kode_aturan' => 'RWD-16',
            'nama_aturan' => 'Pemilahan Plastik',
            'status' => 'aktif',
        ]);

        $this->browse(function (Browser $browser) use ($aturan): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward')
                ->assertSee($aturan->kode_aturan)
                ->assertSee('Pemilahan Plastik')
                ->assertSee('Aktif')
                ->assertSee('Edit');
        });
    }

    public function test_admin_menambah_aturan_pengurangan_dari_form(): void
    {
        $kategori = KategoriSampah::factory()->create(['nama_kategori' => 'Kertas']);

        $this->browse(function (Browser $browser) use ($kategori): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward?tambah=1')
                ->type('nama_aturan', 'Koreksi salah kategori')
                ->click('label:has(input[value="Pengurangan"])')
                ->select('id_kategori', (string) $kategori->id_kategori)
                ->type('nilai_poin', '10')
                ->select('satuan', 'flat')
                ->type('berlaku_mulai', '2026-09-01')
                ->press('Simpan Aturan')
                ->waitForText('Aturan reward berhasil ditambahkan.')
                ->assertSee('Koreksi salah kategori');
        });

        $this->assertDatabaseHas('aturan_reward', [
            'nama_aturan' => 'Koreksi salah kategori',
            'jenis_aturan' => 'Pengurangan',
        ]);
    }

    public function test_admin_mengaktifkan_kembali_aturan_nonaktif_dari_daftar(): void
    {
        $aturan = AturanReward::factory()->create(['status' => 'nonaktif']);

        $this->browse(function (Browser $browser) use ($aturan): void {
            $browser->loginAs($this->admin())
                ->visit('/admin/aturan-reward')
                ->assertSee($aturan->kode_aturan)
                ->press('Aktifkan')
                ->waitForText('Status aturan reward berhasil diperbarui.')
                ->assertSee('Nonaktifkan');
        });

        $this->assertSame('aktif', $aturan->fresh()->status);
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_pengelolaan_aturan_reward(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->pengguna())
                ->visit('/admin/aturan-reward')
                ->assertSee('403');
        });
    }
}
