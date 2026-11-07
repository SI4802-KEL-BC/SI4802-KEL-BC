<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman-halaman PBI-13 saling tersambung: sidebar, tautan antar halaman, pesan setelah aksi.
 * (Halaman login sengaja TIDAK disambungkan: tetap stub terpisah, lihat LoginStubTest.)
 */
class NavigasiAntarHalamanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function aktivitasMilik(Pengguna $pengguna, array $state = []): AktivitasPemilahan
    {
        $lokasi = LokasiTempatSampah::factory()->create(['nama_lokasi' => 'Kantin Pusat']);
        $sesi = SesiPembuangan::factory()->create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_lokasi' => $lokasi->id_lokasi,
            'status_sesi' => SesiPembuangan::SELESAI,
        ]);

        return AktivitasPemilahan::factory()->create($state + ['id_sesi' => $sesi->id_sesi]);
    }

    private function antreanRagu(Pengguna $pengguna): AktivitasPemilahan
    {
        $aktivitas = $this->aktivitasMilik($pengguna);
        VerifikasiSampah::factory()->ragu(0.55)->create(['id_aktivitas' => $aktivitas->id_aktivitas]);

        return $aktivitas;
    }

    // ---------------------------------------------------------------- sidebar

    public function test_sidebar_pengguna_biasa_tidak_memuat_menu_admin(): void
    {
        $this->actingAs(Pengguna::factory()->create())
            ->get('/pindai')
            ->assertOk()
            ->assertSee('Pindai QR')
            ->assertDontSee('ADMIN', false)
            ->assertDontSee('/admin/verifikasi', false);
    }

    public function test_sidebar_admin_memuat_menu_verifikasi_manual(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create())
            ->get('/pindai')
            ->assertOk()
            ->assertSee('ADMIN', false)
            ->assertSee(url('/admin/verifikasi'), false);
    }

    public function test_peran_admin_huruf_kecil_tetap_dikenali_sidebar_dan_middleware(): void
    {
        $admin = Pengguna::factory()->create(['peran' => 'admin']);

        $this->actingAs($admin)->get('/pindai')->assertSee(url('/admin/verifikasi'), false);
        $this->actingAs($admin)->get('/admin/verifikasi')->assertOk();
    }

    public function test_menu_milik_pbi_lain_tampil_nonaktif_bukan_tautan_mati(): void
    {
        $html = $this->actingAs(Pengguna::factory()->create())->get('/pindai')->assertOk()->getContent();

        foreach (['Beranda', 'Panduan Pemilahan', 'Jadwal Pengangkutan', 'Peta Tempat Sampah', 'Poin Saya', 'Alur Pengelolaan'] as $menu) {
            $this->assertMatchesRegularExpression(
                '#<a href="\#"[^>]*aria-disabled="true"[^>]*>(?:(?!</a>).)*'.preg_quote($menu, '#').'#s',
                $html,
                "Menu \"$menu\" seharusnya nonaktif."
            );
        }

        // PBI-14: "Aktivitas Saya" sudah aktif dan mengarah ke riwayat.
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(url('/aktivitas'), '#').'"(?:(?!aria-disabled)[^>])*>(?:(?!</a>).)*Aktivitas Saya#s', $html);
    }

    public function test_menu_pindai_qr_aktif_dan_disorot_di_halaman_pindai(): void
    {
        $html = $this->actingAs(Pengguna::factory()->create())->get('/pindai')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(url('/pindai'), '#').'"\s+class="[^"]*bg-\[\#16A34A\]/25#', $html);
        $this->assertDoesNotMatchRegularExpression('#<a href="'.preg_quote(url('/pindai'), '#').'"[^>]*aria-disabled#', $html);
    }

    // --------------------------------------------------------- pengguna biasa

    public function test_pindai_menampilkan_aktivitas_terakhir_dengan_tautan_ke_detail(): void
    {
        $pengguna = Pengguna::factory()->create();
        $aktivitas = $this->aktivitasMilik($pengguna);
        $milikOrangLain = $this->aktivitasMilik(Pengguna::factory()->create());

        $this->actingAs($pengguna)
            ->get('/pindai')
            ->assertOk()
            ->assertSee(url('/aktivitas/'.$aktivitas->id_aktivitas), false)
            ->assertSee('Kantin Pusat')
            ->assertDontSee(url('/aktivitas/'.$milikOrangLain->id_aktivitas), false)
            ->assertDontSee('Belum ada aktivitas');
    }

    public function test_pindai_hanya_menampilkan_tiga_aktivitas_terbaru(): void
    {
        $pengguna = Pengguna::factory()->create();
        $semua = collect(range(1, 4))->map(fn ($i) => $this->aktivitasMilik($pengguna, [
            'tanggal_waktu' => now()->subHours(10 - $i),
        ]));

        $response = $this->actingAs($pengguna)->get('/pindai')->assertOk();

        $response->assertDontSee(url('/aktivitas/'.$semua[0]->id_aktivitas), false); // yang tertua tidak tampil
        foreach ($semua->slice(1) as $aktivitas) {
            $response->assertSee(url('/aktivitas/'.$aktivitas->id_aktivitas), false);
        }
    }

    public function test_pindai_tanpa_aktivitas_menampilkan_keadaan_kosong(): void
    {
        $this->actingAs(Pengguna::factory()->create())
            ->get('/pindai')
            ->assertOk()
            ->assertSee('Belum ada aktivitas')
            ->assertSee('Belum ada sesi aktif');
    }

    public function test_pindai_menampilkan_sesi_aktif_setelah_qr_tong_dipindai(): void
    {
        $pengguna = Pengguna::factory()->create();
        $lokasi = LokasiTempatSampah::factory()->create(['nama_lokasi' => 'Perpustakaan Lt. 1']);
        SesiPembuangan::factory()->create(['id_pengguna' => $pengguna->id_pengguna, 'id_lokasi' => $lokasi->id_lokasi]);
        // Sesi kedaluwarsa milik pengguna lain / sudah lewat tidak boleh dianggap aktif.
        SesiPembuangan::factory()->kedaluwarsaLewat()->create(['id_pengguna' => $pengguna->id_pengguna]);

        $this->actingAs($pengguna)
            ->get('/pindai')
            ->assertOk()
            ->assertSee('QR berhasil dipindai di Perpustakaan Lt. 1')
            ->assertDontSee('Belum ada sesi aktif');
    }

    public function test_sesi_yang_sudah_lewat_batas_tidak_dianggap_aktif(): void
    {
        $pengguna = Pengguna::factory()->create();
        SesiPembuangan::factory()->kedaluwarsaLewat()->create(['id_pengguna' => $pengguna->id_pengguna]);

        $this->actingAs($pengguna)->get('/pindai')->assertOk()->assertSee('Belum ada sesi aktif');
    }

    public function test_detail_aktivitas_menaut_kembali_ke_halaman_pindai(): void
    {
        $pengguna = Pengguna::factory()->create();
        $aktivitas = $this->aktivitasMilik($pengguna);

        $this->actingAs($pengguna)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee('href="'.url('/pindai').'"', false);
    }

    public function test_detail_aktivitas_memuat_hak_ubah_dari_policy_lewat_can_update(): void
    {
        $pengguna = Pengguna::factory()->create();
        $menunggu = $this->aktivitasMilik($pengguna);
        $final = $this->aktivitasMilik($pengguna, ['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI]);

        $this->actingAs($pengguna)->get(route('aktivitas.show', $menunggu))
            ->assertSee('Ubah Catatan')->assertSee('Batalkan Aktivitas');
        $this->actingAs($pengguna)->get(route('aktivitas.show', $final))
            ->assertDontSee('Ubah Catatan')->assertDontSee('Batalkan Aktivitas');
    }

    public function test_detail_aktivitas_menampilkan_langkah_dari_relasi_sesi_dan_verifikator(): void
    {
        $pengguna = Pengguna::factory()->create();
        $admin = Pengguna::factory()->admin()->create(['nama_lengkap' => 'Petugas Budi']);
        $aktivitas = $this->aktivitasMilik($pengguna, ['status_verifikasi' => AktivitasPemilahan::DITOLAK, 'alasan_penolakan' => 'Foto buram']);
        VerifikasiSampah::factory()->ragu()->create(['id_aktivitas' => $aktivitas->id_aktivitas]);
        VerifikasiSampah::factory()->create([
            'id_aktivitas' => $aktivitas->id_aktivitas, 'id_verifikator' => $admin->id_pengguna,
            'tahap' => VerifikasiSampah::TAHAP_MANUAL, 'hasil' => VerifikasiSampah::TIDAK_VALID,
        ]);

        $this->actingAs($pengguna)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee($aktivitas->sesi->waktu_scan->format('d M Y, H:i')) // relasi sesi
            ->assertSee('Petugas Budi')                                      // relasi verifikasi.verifikator
            ->assertSee('Alasan: Foto buram');
    }

    public function test_setelah_membatalkan_kembali_ke_pindai_dengan_pesan_di_layout(): void
    {
        $pengguna = Pengguna::factory()->create();
        $aktivitas = $this->aktivitasMilik($pengguna);

        $this->actingAs($pengguna)
            ->followingRedirects()
            ->delete(route('aktivitas.destroy', $aktivitas))
            ->assertOk()
            ->assertSee('Aktivitas berhasil dibatalkan.')
            ->assertSee('Belum ada aktivitas');
    }

    public function test_setelah_mengubah_catatan_kembali_ke_detail_dengan_pesan_di_layout(): void
    {
        $pengguna = Pengguna::factory()->create();
        $aktivitas = $this->aktivitasMilik($pengguna);

        $this->actingAs($pengguna)
            ->followingRedirects()
            ->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => 'Botol bekas kantin'])
            ->assertOk()
            ->assertSee('Catatan berhasil disimpan.')
            ->assertSee('Botol bekas kantin');
    }

    // ------------------------------------------------------------------ admin

    public function test_antrean_admin_menaut_ke_detail_dan_detail_kembali_ke_antrean(): void
    {
        $admin = Pengguna::factory()->admin()->create();
        $aktivitas = $this->antreanRagu(Pengguna::factory()->create());

        $this->actingAs($admin)->get('/admin/verifikasi')
            ->assertOk()
            ->assertSee(route('admin.verifikasi.show', $aktivitas), false);

        $this->actingAs($admin)->get(route('admin.verifikasi.show', $aktivitas))
            ->assertOk()
            ->assertSee(route('admin.verifikasi.index'), false);
    }

    public function test_antrean_admin_memakai_field_keputusan_untuk_setujui_dan_tolak(): void
    {
        $admin = Pengguna::factory()->admin()->create();
        $aktivitas = $this->antreanRagu(Pengguna::factory()->create());

        $html = $this->actingAs($admin)->get('/admin/verifikasi')->assertOk()->getContent();

        $this->assertStringContainsString('name="keputusan" value="setujui"', $html);
        $this->assertStringContainsString('name="keputusan" value="tolak"', $html);
        $this->assertStringContainsString(url('/admin/verifikasi/'.$aktivitas->id_aktivitas), $html); // aksi form
    }

    public function test_setelah_keputusan_kembali_ke_antrean_dengan_pesan_di_layout(): void
    {
        $admin = Pengguna::factory()->admin()->create();
        $aktivitas = $this->antreanRagu(Pengguna::factory()->create());

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertOk()
            ->assertSee('Aktivitas disetujui.')
            ->assertSee('Antrean kosong');
    }

    public function test_aktivitas_yang_disetujui_admin_terlihat_pengguna_di_detail_dan_halaman_pindai(): void
    {
        $pengguna = Pengguna::factory()->create();
        $admin = Pengguna::factory()->admin()->create();
        $aktivitas = $this->antreanRagu($pengguna);

        $this->actingAs($admin)->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'tolak', 'alasan' => 'Foto buram']);

        $this->actingAs($pengguna)->get(route('aktivitas.show', $aktivitas))
            ->assertOk()->assertSee('Ditolak')->assertSee('Alasan: Foto buram');
        $this->actingAs($pengguna)->get('/pindai')
            ->assertOk()->assertSee(url('/aktivitas/'.$aktivitas->id_aktivitas), false)->assertSee('Ditolak');
    }
}
