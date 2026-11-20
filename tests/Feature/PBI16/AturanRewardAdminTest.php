<?php

namespace Tests\Feature\PBI16;

use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\Pengguna;
use App\Models\TransaksiPoin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanRewardAdminTest extends TestCase
{
    use RefreshDatabase;

    private KategoriSampah $plastik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Pengguna::factory()->admin()->create());
        $this->plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
    }

    public function test_tc_16_01_admin_melihat_daftar_aturan_dan_detailnya(): void
    {
        $aturan = AturanReward::factory()->create([
            'id_kategori' => $this->plastik->id_kategori,
            'kode_aturan' => 'RWD-01',
            'nama_aturan' => 'Pemilahan Plastik',
            'batas_per_hari' => 50,
            'status' => 'aktif',
        ]);

        $this->get(route('admin.aturan-reward.index', ['edit' => $aturan->id_aturan]))
            ->assertOk()
            ->assertSee('RWD-01')
            ->assertSee('Pemilahan Plastik')
            ->assertSee('Plastik')
            ->assertSee('Aktif')
            ->assertSee('Batas harian')
            ->assertSee('value="50"', false);
    }

    public function test_tc_16_02_admin_menambah_aturan_penambahan(): void
    {
        $this->post(route('admin.aturan-reward.store'), $this->dataAturanValid([
            'nama_aturan' => 'Pemilahan Plastik',
            'nilai_poin' => 5,
            'batas_per_hari' => 50,
        ]))
            ->assertRedirect(route('admin.aturan-reward.index'))
            ->assertSessionHas('status', 'Aturan reward berhasil ditambahkan.');

        $this->assertDatabaseHas('aturan_reward', [
            'nama_aturan' => 'Pemilahan Plastik',
            'id_kategori' => $this->plastik->id_kategori,
            'jenis_aturan' => 'Penambahan',
            'poin_per_satuan' => 5,
            'satuan' => 'per item',
            'batas_per_hari' => 50,
            'status' => 'aktif',
        ]);

        $this->assertSame('2026-09-01', AturanReward::query()
            ->where('nama_aturan', 'Pemilahan Plastik')
            ->firstOrFail()
            ->berlaku_mulai
            ->format('Y-m-d'));
    }

    public function test_tc_16_03_admin_menambah_aturan_pengurangan_dengan_nilai_tersimpan_positif(): void
    {
        $this->post(route('admin.aturan-reward.store'), $this->dataAturanValid([
            'nama_aturan' => 'Koreksi salah kategori',
            'jenis_aturan' => 'Pengurangan',
            'nilai_poin' => 10,
            'satuan' => 'flat',
        ]))
            ->assertRedirect(route('admin.aturan-reward.index'))
            ->assertSessionHas('status', 'Aturan reward berhasil ditambahkan.');

        $this->assertDatabaseHas('aturan_reward', [
            'nama_aturan' => 'Koreksi salah kategori',
            'jenis_aturan' => 'Pengurangan',
            'poin_per_satuan' => 10,
        ]);
    }

    public function test_tc_16_04_admin_mengubah_nilai_dan_batas_harian_tanpa_mengganti_id(): void
    {
        $aturan = AturanReward::factory()->create([
            'id_kategori' => $this->plastik->id_kategori,
            'poin_per_satuan' => 5,
            'batas_per_hari' => 50,
        ]);

        $this->put(route('admin.aturan-reward.update', $aturan), $this->dataAturanValid([
            'nama_aturan' => $aturan->nama_aturan,
            'nilai_poin' => 6,
            'batas_per_hari' => 60,
        ]))
            ->assertRedirect(route('admin.aturan-reward.index'))
            ->assertSessionHas('status', 'Aturan reward berhasil diperbarui.');

        $this->assertDatabaseHas('aturan_reward', [
            'id_aturan' => $aturan->id_aturan,
            'poin_per_satuan' => 6,
            'batas_per_hari' => 60,
        ]);
        $this->assertDatabaseCount('aturan_reward', 1);
    }

    public function test_tc_16_05_menonaktifkan_aturan_tanpa_menghapusnya(): void
    {
        $aturan = AturanReward::factory()->create(['id_kategori' => $this->plastik->id_kategori]);

        $this->patch(route('admin.aturan-reward.toggle', $aturan))
            ->assertRedirect()
            ->assertSessionHas('status', 'Status aturan reward berhasil diperbarui.');

        $this->assertDatabaseHas('aturan_reward', [
            'id_aturan' => $aturan->id_aturan,
            'status' => 'nonaktif',
        ]);
        $this->assertModelExists($aturan);
    }

    public function test_tc_16_06_admin_mengaktifkan_kembali_aturan_nonaktif(): void
    {
        $aturan = AturanReward::factory()->create([
            'id_kategori' => $this->plastik->id_kategori,
            'status' => 'nonaktif',
        ]);

        $this->patch(route('admin.aturan-reward.toggle', $aturan))
            ->assertRedirect()
            ->assertSessionHas('status', 'Status aturan reward berhasil diperbarui.');

        $this->assertSame('aktif', $aturan->fresh()->status);
    }

    public function test_tc_16_07_mengubah_aturan_tidak_mengubah_transaksi_lama(): void
    {
        $aturan = AturanReward::factory()->create([
            'id_kategori' => $this->plastik->id_kategori,
            'poin_per_satuan' => 5,
        ]);
        $transaksiLama = TransaksiPoin::factory()->create([
            'id_aturan' => $aturan->id_aturan,
            'jumlah_poin' => 25,
        ]);

        $this->put(route('admin.aturan-reward.update', $aturan), $this->dataAturanValid([
            'nama_aturan' => $aturan->nama_aturan,
            'nilai_poin' => 6,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(6, $aturan->fresh()->poin_per_satuan);
        $this->assertSame(25, $transaksiLama->fresh()->jumlah_poin);
    }

    public function test_tc_16_12_point_engine_mengabaikan_aturan_nonaktif(): void
    {
        $this->markTestIncomplete('PointService is a temporary PBI-13 stub; verify this contract when the Point Engine is implemented.');
    }

    /** @return array<string, mixed> */
    private function dataAturanValid(array $overrides = []): array
    {
        return array_replace([
            'nama_aturan' => 'Pemilahan Plastik',
            'jenis_aturan' => 'Penambahan',
            'id_kategori' => $this->plastik->id_kategori,
            'syarat' => ['aktivitas_terverifikasi'],
            'nilai_poin' => 5,
            'satuan' => 'per item',
            'batas_per_hari' => null,
            'berlaku_mulai' => '2026-09-01',
            'aktif' => 1,
        ], $overrides);
    }
}
