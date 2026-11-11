<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilPenggunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_must_sign_in_to_view_or_update_a_profile(): void
    {
        $this->get(route('profil.show'))->assertRedirect(route('login'));
        $this->get(route('profil.edit'))->assertRedirect(route('login'));
        $this->put(route('profil.update'), ['nama_lengkap' => 'Updated'])->assertRedirect(route('login'));
    }

    public function test_signed_in_user_can_view_their_profile_and_edit_form(): void
    {
        $user = Pengguna::factory()->create([
            'nama_lengkap' => 'Profile Owner',
            'nama_panggilan' => 'Owner',
            'nim_nip' => '123456',
            'program_studi' => 'Informatika',
            'no_hp' => '08123456789',
        ]);

        $this->actingAs($user)
            ->get(route('profil.show'))
            ->assertOk()
            ->assertSee('Profile Owner')
            ->assertSee('123456')
            ->assertSee('Informatika');

        $this->actingAs($user)
            ->get(route('profil.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Profil')
            ->assertSee('Profile Owner')
            ->assertSee(route('profil.update'))
            ->assertDontSee('Ubah Kata Sandi')
            ->assertDontSee('Nonaktifkan Akun');
    }

    public function test_profile_update_changes_only_the_signed_in_user(): void
    {
        $user = Pengguna::factory()->create(['nama_lengkap' => 'Profile Owner']);
        $otherUser = Pengguna::factory()->create(['nama_lengkap' => 'Other User']);

        $this->actingAs($user)
            ->put(route('profil.update'), [
                'nama_lengkap' => 'Updated Owner',
                'nama_panggilan' => 'Updated',
                'program_studi' => 'Sistem Informasi',
                'no_hp' => '08111111111',
            ])
            ->assertRedirect(route('profil.show'))
            ->assertSessionHas('status', 'Profil berhasil diperbarui!');

        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $user->id_pengguna,
            'nama_lengkap' => 'Updated Owner',
            'nama_panggilan' => 'Updated',
            'program_studi' => 'Sistem Informasi',
            'no_hp' => '08111111111',
        ]);
        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $otherUser->id_pengguna,
            'nama_lengkap' => 'Other User',
        ]);
    }
}
