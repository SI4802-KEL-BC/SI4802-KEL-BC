<?php

namespace Tests\Feature\PBI16;

use App\Models\AturanReward;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanRewardAksesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tc_16_13_pengunjung_tanpa_login_diarahkan_ke_login(): void
    {
        $aturan = AturanReward::factory()->create();

        $this->get(route('admin.aturan-reward.index'))
            ->assertRedirect(route('login'));
        $this->post(route('admin.aturan-reward.store'), [])
            ->assertRedirect(route('login'));
        $this->put(route('admin.aturan-reward.update', $aturan), [])
            ->assertRedirect(route('login'));
        $this->patch(route('admin.aturan-reward.toggle', $aturan))
            ->assertRedirect(route('login'));
    }

    public function test_tc_16_13_pengguna_biasa_ditolak_dari_semua_endpoint_kelola(): void
    {
        $aturan = AturanReward::factory()->create();

        $this->actingAs(Pengguna::factory()->create());

        $this->get(route('admin.aturan-reward.index'))->assertForbidden();
        $this->post(route('admin.aturan-reward.store'), [])->assertForbidden();
        $this->put(route('admin.aturan-reward.update', $aturan), [])->assertForbidden();
        $this->patch(route('admin.aturan-reward.toggle', $aturan))->assertForbidden();

        $this->assertSame('aktif', $aturan->fresh()->status);
    }
}
