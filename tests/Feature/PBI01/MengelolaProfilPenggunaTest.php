<?php

namespace Tests\Feature\PBI01;

use App\Models\Pengguna;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SCRUM-24 · PBI-1 CRUD Profil Pengguna
 *
 * Use Case  : Mengelola Profil Pengguna
 * Aktor     : User
 * Awal      : Pengguna membuka website PilahKu (ubah/nonaktifkan wajib login)
 * Akhir     : Profil berhasil dibuat, diperbarui, atau dinonaktifkan
 */
class MengelolaProfilPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function dataRegistrasi(array $override = []): array
    {
        return $override + [
            'nama_lengkap' => 'Nadia Putri',
            'nama_panggilan' => 'Nad',
            'nim_nip' => '102022400017',
            'email_kampus' => 'nadia@student.telkomuniversity.ac.id',
            'program_studi' => 'S1 Sistem Informasi',
            'no_hp' => '081234567890',
            'kata_sandi' => 'rahasia123',
            'kata_sandi_confirmation' => 'rahasia123',
        ];
    }

    private function penggunaAktif(array $override = []): Pengguna
    {
        return Pengguna::factory()->create($override + [
            'nama_lengkap' => 'Nadia Putri',
            'nama_panggilan' => 'Nad',
            'kata_sandi' => Hash::make('rahasia123'),
        ]);
    }

    // ------------------------------------------------------------------ Test Case

    /** TC-P01-01 · Registrasi akun dengan data valid → akun dibuat dan tersimpan di tabel pengguna. */
    public function test_tc_p01_01_registrasi_akun_dengan_data_valid(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Buat akun baru');

        $this->post(route('register.simpan'), $this->dataRegistrasi())
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun berhasil dibuat. Silakan masuk.');

        $pengguna = Pengguna::where('email_kampus', 'nadia@student.telkomuniversity.ac.id')->firstOrFail();
        $this->assertSame('Nadia Putri', $pengguna->nama_lengkap);
        $this->assertSame('102022400017', $pengguna->nim_nip);
        $this->assertSame('S1 Sistem Informasi', $pengguna->program_studi);
        $this->assertSame('User', $pengguna->peran);
        $this->assertSame('aktif', $pengguna->status_akun);
        $this->assertNotNull($pengguna->tanggal_bergabung);
        // Kata sandi disimpan dalam bentuk hash.
        $this->assertNotSame('rahasia123', $pengguna->kata_sandi);
        $this->assertTrue(Hash::check('rahasia123', $pengguna->kata_sandi));
    }

    /** TC-P01-02 · Mengubah nama panggilan dari Nad menjadi Nadia → profil diperbarui. */
    public function test_tc_p01_02_mengubah_profil_dengan_data_valid(): void
    {
        $pengguna = $this->penggunaAktif();

        $this->actingAs($pengguna)
            ->put(route('profil.update'), ['nama_lengkap' => 'Nadia Putri', 'nama_panggilan' => 'Nadia'])
            ->assertRedirect(route('profil.show'))
            ->assertSessionHas('status', 'Profil berhasil diperbarui!');

        $this->assertSame('Nadia', $pengguna->fresh()->nama_panggilan);
    }

    /** TC-P01-03 · Nonaktifkan akun dengan konfirmasi dan kata sandi → status Nonaktif, data tetap ada. */
    public function test_tc_p01_03_menonaktifkan_akun_dengan_konfirmasi(): void
    {
        $pengguna = $this->penggunaAktif();

        $this->actingAs($pengguna)
            ->post(route('profil.deactivate'), ['konfirmasi' => 'NONAKTIFKAN', 'password' => 'rahasia123'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun berhasil dinonaktifkan.');

        $this->assertGuest();
        $this->assertSame('nonaktif', $pengguna->fresh()->status_akun);
        $this->assertModelExists($pengguna);

        // Akun Nonaktif tidak bisa login lagi.
        $this->post(route('login.masuk'), ['email' => $pengguna->email_kampus, 'kata_sandi' => 'rahasia123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** TC-P01-04 · Nama lengkap atau email kampus dikosongkan → registrasi ditolak dengan pesan. */
    public function test_tc_p01_04_registrasi_dengan_data_tidak_lengkap(): void
    {
        $this->from(route('register'))
            ->post(route('register.simpan'), $this->dataRegistrasi(['nama_lengkap' => '', 'email_kampus' => '']))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'nama_lengkap' => 'Nama lengkap wajib diisi.',
                'email_kampus' => 'Email kampus wajib diisi.',
            ]);

        $this->assertDatabaseCount('pengguna', 0);

        // Pesan kesalahan tampil di halaman registrasi.
        $this->from(route('register'))->followingRedirects()
            ->post(route('register.simpan'), $this->dataRegistrasi(['nama_lengkap' => '', 'email_kampus' => '']))
            ->assertOk()
            ->assertSee('Nama lengkap wajib diisi.')
            ->assertSee('Email kampus wajib diisi.');
    }

    /** TC-P01-05 · Data profil dengan format tidak sesuai → perubahan ditolak, diminta memperbaiki. */
    public function test_tc_p01_05_mengubah_profil_dengan_data_tidak_valid(): void
    {
        $pengguna = $this->penggunaAktif(['no_hp' => '081234567890']);

        $this->actingAs($pengguna)
            ->from(route('profil.edit'))
            ->put(route('profil.update'), [
                'nama_lengkap' => '',
                'nama_panggilan' => str_repeat('a', 51),
                'no_hp' => 'nomor-hp',
            ])
            ->assertRedirect(route('profil.edit'))
            ->assertSessionHasErrors([
                'nama_lengkap' => 'Nama lengkap wajib diisi.',
                'nama_panggilan' => 'Nama panggilan maksimal 50 karakter.',
                'no_hp' => 'Format nomor HP tidak valid.',
            ]);

        $pengguna->refresh();
        $this->assertSame('Nadia Putri', $pengguna->nama_lengkap);
        $this->assertSame('081234567890', $pengguna->no_hp);

        // Pengguna diminta memperbaiki data: pesan tampil di form ubah profil.
        $this->actingAs($pengguna)->from(route('profil.edit'))->followingRedirects()
            ->put(route('profil.update'), ['nama_lengkap' => 'Nadia Putri', 'no_hp' => 'nomor-hp'])
            ->assertOk()
            ->assertSee('Format nomor HP tidak valid.');
    }

    /** TC-P01-06 · Batal pada dialog penonaktifan → status tetap Aktif, tidak ada perubahan data. */
    public function test_tc_p01_06_membatalkan_konfirmasi_nonaktifkan_akun(): void
    {
        $pengguna = $this->penggunaAktif();

        // Dialog konfirmasi di halaman memakai confirm(); Batal berarti form tidak dikirim.
        $this->actingAs($pengguna)->get(route('profil.edit'))
            ->assertSee("return confirm('Apakah Anda yakin ingin menonaktifkan akun?", false);

        // Konfirmasi tidak diketik atau kata sandi salah juga tidak menonaktifkan akun.
        $this->actingAs($pengguna)
            ->post(route('profil.deactivate'), ['konfirmasi' => '', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('konfirmasi');
        $this->actingAs($pengguna)
            ->post(route('profil.deactivate'), ['konfirmasi' => 'NONAKTIFKAN', 'password' => 'salah'])
            ->assertSessionHasErrors(['password' => 'Kata sandi salah.']);

        $this->assertSame('aktif', $pengguna->fresh()->status_akun);
        $this->assertAuthenticatedAs($pengguna);
    }

    // ------------------------------------------------------------------ Use Case

    /** Skenario Normal: registrasi → buka Profil Saya → ubah profil → nonaktifkan akun. */
    public function test_use_case_skenario_normal(): void
    {
        // 1–2. Mengisi form registrasi → sistem memvalidasi dan menyimpan akun.
        $this->post(route('register.simpan'), $this->dataRegistrasi())->assertRedirect(route('login'));

        // Pengguna login.
        $this->post(route('login.masuk'), ['email' => 'nadia@student.telkomuniversity.ac.id', 'kata_sandi' => 'rahasia123'])
            ->assertRedirect();
        $pengguna = Pengguna::where('email_kampus', 'nadia@student.telkomuniversity.ac.id')->firstOrFail();
        $this->assertAuthenticatedAs($pengguna);

        // 3–4. Membuka Profil Saya → sistem menampilkan data profil.
        $this->get(route('profil.show'))->assertOk()->assertSee('Nadia Putri')->assertSee('102022400017');

        // 5–6. Mengubah profil dan menyimpan → sistem memvalidasi dan menyimpan perubahan.
        $this->put(route('profil.update'), ['nama_lengkap' => 'Nadia Putri', 'nama_panggilan' => 'Nadia'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Nadia', $pengguna->fresh()->nama_panggilan);

        // 7–8. Menonaktifkan akun dengan konfirmasi → status akun menjadi Nonaktif.
        $this->post(route('profil.deactivate'), ['konfirmasi' => 'NONAKTIFKAN', 'password' => 'rahasia123'])
            ->assertRedirect(route('login'));
        $this->assertSame('nonaktif', $pengguna->fresh()->status_akun);
    }

    /** Skenario Gagal: data tidak lengkap ditolak; Batal saat konfirmasi membuat akun tetap aktif. */
    public function test_use_case_skenario_gagal(): void
    {
        // 1–2. Mengisi data tidak lengkap / tidak valid → ditolak dengan pesan kesalahan.
        $this->post(route('register.simpan'), $this->dataRegistrasi([
            'email_kampus' => 'bukan-email',
            'kata_sandi_confirmation' => 'beda',
        ]))->assertSessionHasErrors([
            'email_kampus' => 'Format email kampus tidak valid.',
            'kata_sandi' => 'Konfirmasi kata sandi tidak cocok.',
        ]);
        $this->assertDatabaseCount('pengguna', 0);

        // 3–4. Memilih Batal saat konfirmasi nonaktif → status akun tetap aktif.
        $pengguna = $this->penggunaAktif();
        $this->actingAs($pengguna)
            ->post(route('profil.deactivate'), ['konfirmasi' => 'BATAL', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('konfirmasi');
        $this->assertSame('aktif', $pengguna->fresh()->status_akun);
    }

    public function test_email_dan_nim_yang_sudah_terdaftar_ditolak(): void
    {
        $this->penggunaAktif(['email_kampus' => 'nadia@student.telkomuniversity.ac.id', 'nim_nip' => '102022400017']);

        $this->post(route('register.simpan'), $this->dataRegistrasi())
            ->assertSessionHasErrors([
                'email_kampus' => 'Email kampus sudah terdaftar.',
                'nim_nip' => 'NIM/NIP sudah terdaftar.',
            ]);
        $this->assertDatabaseCount('pengguna', 1);
    }

    /** Design Tabel pengguna: nim_nip unique, panjang kolom sesuai dokumen. */
    public function test_tabel_pengguna_sesuai_design_tabel(): void
    {
        $this->penggunaAktif(['nim_nip' => '102022400017']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->penggunaAktif(['nim_nip' => '102022400017']);
    }

    public function test_migration_desain_tabel_berhenti_bila_ada_nim_dobel(): void
    {
        $migration = require database_path('migrations/2026_11_18_000000_sesuaikan_kolom_pengguna_dan_kategori_dengan_desain.php');
        $migration->down();

        $this->penggunaAktif(['nim_nip' => '102022400017']);
        $this->penggunaAktif(['nim_nip' => '102022400017']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('NIM/NIP dobel di tabel pengguna: 102022400017');
        $migration->up();
    }

    public function test_nomor_hp_lebih_dari_15_karakter_ditolak(): void
    {
        $pengguna = $this->penggunaAktif();

        // "+62" + 13 digit = 16 karakter, melebihi kolom no_hp VARCHAR(15).
        $this->actingAs($pengguna)
            ->put(route('profil.update'), ['nama_lengkap' => 'Nadia Putri', 'no_hp' => '+626281234567890'])
            ->assertSessionHasErrors(['no_hp' => 'Format nomor HP tidak valid.']);

        $this->actingAs($pengguna)
            ->put(route('profil.update'), ['nama_lengkap' => 'Nadia Putri', 'no_hp' => '+6281234567890'])
            ->assertSessionHasNoErrors();
        $this->assertSame('+6281234567890', $pengguna->fresh()->no_hp);
    }

    public function test_ubah_kata_sandi_memakai_kata_sandi_lama(): void
    {
        $pengguna = $this->penggunaAktif();

        $this->actingAs($pengguna)
            ->put(route('profil.password'), ['password_lama' => 'salah', 'kata_sandi' => 'sandibaru123'])
            ->assertSessionHasErrors(['password_lama' => 'Kata sandi saat ini salah.']);

        $this->actingAs($pengguna)
            ->put(route('profil.password'), ['password_lama' => 'rahasia123', 'kata_sandi' => 'sandibaru123'])
            ->assertRedirect(route('profil.edit'))
            ->assertSessionHas('status', 'Kata sandi berhasil diperbarui.');

        $this->assertTrue(Hash::check('sandibaru123', $pengguna->fresh()->kata_sandi));
    }

    public function test_halaman_login_menaut_ke_registrasi_dan_registrasi_hanya_untuk_tamu(): void
    {
        $this->get(route('login'))->assertSee('href="'.route('register').'"', false);

        $this->actingAs($this->penggunaAktif())->get(route('register'))->assertRedirect();
    }
}
