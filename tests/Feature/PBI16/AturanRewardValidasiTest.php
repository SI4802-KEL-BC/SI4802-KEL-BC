<?php

namespace Tests\Feature\PBI16;

use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanRewardValidasiTest extends TestCase
{
    use RefreshDatabase;

    private KategoriSampah $plastik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Pengguna::factory()->admin()->create());
        $this->plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
    }

    public function test_tc_16_08_menolak_field_wajib_kosong(): void
    {
        $this->from(route('admin.aturan-reward.index'))
            ->post(route('admin.aturan-reward.store'), [])
            ->assertRedirect(route('admin.aturan-reward.index'))
            ->assertSessionHasErrors(['nama_aturan', 'jenis_aturan', 'nilai_poin', 'satuan']);

        $this->assertDatabaseCount('aturan_reward', 0);
    }

    public function test_tc_16_08_tanggal_berlaku_wajib_diisi(): void
    {
        $data = $this->dataAturanValid();
        unset($data['berlaku_mulai']);

        $this->post(route('admin.aturan-reward.store'), $data)
            ->assertSessionHasErrors(['berlaku_mulai']);

        $this->assertDatabaseCount('aturan_reward', 0);
    }

    public function test_kategori_kosong_diizinkan_untuk_aturan_global(): void
    {
        $data = $this->dataAturanValid();
        $data['id_kategori'] = '';

        $this->post(route('admin.aturan-reward.store'), $data)
            ->assertRedirect(route('admin.aturan-reward.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aturan_reward', [
            'nama_aturan' => 'Reward Plastik',
            'id_kategori' => null,
        ]);
    }

    public function test_tc_16_09_menolak_nilai_poin_bukan_integer_atau_tidak_positif(): void
    {
        foreach (['abc', 0, -5] as $nilaiPoin) {
            $this->post(route('admin.aturan-reward.store'), $this->dataAturanValid([
                'nama_aturan' => 'Nilai Tidak Valid '.(string) $nilaiPoin,
                'nilai_poin' => $nilaiPoin,
            ]))->assertSessionHasErrors(['nilai_poin']);
        }

        $this->assertDatabaseCount('aturan_reward', 0);
    }

    public function test_tc_16_10_menolak_batas_harian_nol_negatif_atau_bukan_integer(): void
    {
        foreach ([-1, 'abc', 0] as $batasPerHari) {
            $this->post(route('admin.aturan-reward.store'), $this->dataAturanValid([
                'nama_aturan' => 'Batas Tidak Valid '.(string) $batasPerHari,
                'batas_per_hari' => $batasPerHari,
            ]))->assertSessionHasErrors(['batas_per_hari']);
        }

        $this->assertDatabaseCount('aturan_reward', 0);
    }

    public function test_tc_16_10_menolak_tanggal_berlaku_yang_tidak_valid(): void
    {
        $this->post(route('admin.aturan-reward.store'), $this->dataAturanValid([
            'berlaku_mulai' => '31/02/2026',
        ]))->assertSessionHasErrors(['berlaku_mulai']);

        $this->assertDatabaseCount('aturan_reward', 0);
    }

    public function test_tc_16_11_menolak_aturan_aktif_ganda_untuk_kategori_jenis_dan_satuan_sama(): void
    {
        AturanReward::factory()->create([
            'id_kategori' => $this->plastik->id_kategori,
            'jenis_aturan' => 'Penambahan',
            'satuan' => 'per item',
            'status' => 'aktif',
        ]);

        $this->post(route('admin.aturan-reward.store'), $this->dataAturanValid())
            ->assertSessionHasErrors(['id_kategori']);

        $this->assertDatabaseCount('aturan_reward', 1);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function dataAturanValid(array $overrides = []): array
    {
        return array_replace([
            'nama_aturan' => 'Reward Plastik',
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
