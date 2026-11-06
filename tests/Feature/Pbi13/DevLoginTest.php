<?php

namespace Tests\Feature\Pbi13;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** STUB PBI-13: login pintas hanya boleh ada di APP_ENV=local. */
class DevLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_dev_login_tidak_tersedia_di_luar_env_local(): void
    {
        $pengguna = Pengguna::factory()->create();

        $this->assertSame('testing', app()->environment());
        $this->get('/dev-login/'.$pengguna->email_kampus)->assertNotFound();
        $this->assertGuest();
    }
}
