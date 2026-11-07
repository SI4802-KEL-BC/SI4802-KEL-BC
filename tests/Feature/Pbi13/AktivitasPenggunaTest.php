<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * TC-13-06, 07, 08, 12, 15, 16, 17 (TC-13-16: tanpa login dan non-Admin ke /admin/verifikasi).
 */
class AktivitasPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $pemilik;

    private LokasiTempatSampah $lokasi;

    private KategoriSampah $plastik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->pemilik = Pengguna::factory()->create();
        $this->lokasi = LokasiTempatSampah::factory()->create(['nama_lokasi' => 'Kantin Pusat']);
        $this->plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
    }

    private function aktivitasMilik(Pengguna $pengguna, array $state = []): AktivitasPemilahan
    {
        $sesi = SesiPembuangan::factory()->create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_lokasi' => $this->lokasi->id_lokasi,
            'status_sesi' => SesiPembuangan::SELESAI,
        ]);

        return AktivitasPemilahan::factory()->create($state + [
            'id_sesi' => $sesi->id_sesi,
            'id_kategori' => $this->plastik->id_kategori,
        ]);
    }

    public function test_tc_13_06_detail_aktivitas_sendiri_menampilkan_data_dan_hasil_verifikasi(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik, ['catatan' => 'Botol air mineral']);
        VerifikasiSampah::factory()->ragu(0.55)->create(['id_aktivitas' => $aktivitas->id_aktivitas]);

        $this->actingAs($this->pemilik)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee('Plastik')
            ->assertSee('Kantin Pusat')
            ->assertSee($aktivitas->tanggal_waktu->format('H:i'))
            ->assertSee('Menunggu Validasi')
            ->assertSee('Botol air mineral')
            ->assertSee('Ragu')
            ->assertSee('plastic')
            ->assertSee('55%')
            ->assertSee('storage/'.$aktivitas->foto_bukti, false)
            ->assertSee('Ubah Catatan')
            ->assertSee('Batalkan');
    }

    public function test_tc_13_06_detail_menampilkan_hasil_admin_dan_alasan_penolakan(): void
    {
        $admin = Pengguna::factory()->admin()->create();
        $aktivitas = $this->aktivitasMilik($this->pemilik, [
            'status_verifikasi' => AktivitasPemilahan::DITOLAK,
            'alasan_penolakan' => 'Foto tidak jelas',
        ]);
        VerifikasiSampah::factory()->ragu()->create(['id_aktivitas' => $aktivitas->id_aktivitas]);
        VerifikasiSampah::factory()->create([
            'id_aktivitas' => $aktivitas->id_aktivitas,
            'id_verifikator' => $admin->id_pengguna,
            'tahap' => VerifikasiSampah::TAHAP_MANUAL,
            'hasil' => VerifikasiSampah::TIDAK_VALID,
            'keterangan' => 'Foto tidak jelas',
        ]);

        $this->actingAs($this->pemilik)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Verifikasi Admin')
            ->assertSee('Alasan: Foto tidak jelas');
    }

    public function test_tc_13_07_ubah_catatan_saat_menunggu_validasi(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik);

        $this->actingAs($this->pemilik)
            ->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => 'Botol bekas kantin'])
            ->assertRedirect(route('aktivitas.show', $aktivitas))
            ->assertSessionHas('status');

        $aktivitas->refresh();
        $this->assertSame('Botol bekas kantin', $aktivitas->catatan);
        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->status_verifikasi);
    }

    public function test_tc_13_07_catatan_boleh_dikosongkan_dan_dibatasi_255_karakter(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik, ['catatan' => 'lama']);

        $this->actingAs($this->pemilik)
            ->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => str_repeat('a', 256)])
            ->assertSessionHasErrors('catatan');
        $this->assertSame('lama', $aktivitas->fresh()->catatan);

        $this->actingAs($this->pemilik)
            ->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($aktivitas->fresh()->catatan);
    }

    public function test_tc_13_08_batalkan_menghapus_aktivitas_dan_verifikasinya(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik);
        VerifikasiSampah::factory()->ragu()->create(['id_aktivitas' => $aktivitas->id_aktivitas]);

        // Halaman detail meminta konfirmasi sebelum membatalkan.
        $this->actingAs($this->pemilik)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertSee('Batalkan aktivitas ini?')
            ->assertSee('Ya, Batalkan');

        $this->actingAs($this->pemilik)
            ->delete(route('aktivitas.destroy', $aktivitas))
            ->assertRedirect(route('pindai.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($aktivitas);
        $this->assertDatabaseCount('verifikasi_sampah', 0); // tidak tersisa di antrean verifikasi

        // Dari sisi Admin: tidak muncul di antrean dan tidak bisa diproses.
        $admin = Pengguna::factory()->admin()->create();
        $this->actingAs($admin)
            ->get('/admin/verifikasi')
            ->assertOk()
            ->assertDontSee(route('admin.verifikasi.show', $aktivitas), false)
            ->assertSee('Antrean kosong');
        $this->actingAs($admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function statusFinal(): array
    {
        return [
            'Terverifikasi' => [AktivitasPemilahan::TERVERIFIKASI],
            'Ditolak' => [AktivitasPemilahan::DITOLAK],
        ];
    }

    #[DataProvider('statusFinal')]
    public function test_tc_13_12_ubah_dan_batal_setelah_divalidasi_ditolak(string $status): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik, [
            'status_verifikasi' => $status,
            'catatan' => 'asli',
        ]);

        // Tombol tidak tampil.
        $this->actingAs($this->pemilik)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertDontSee('Ubah Catatan')
            ->assertDontSee('Batalkan');

        // Request tetap ditolak 403.
        $this->actingAs($this->pemilik)
            ->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => 'diubah'])
            ->assertForbidden();
        $this->actingAs($this->pemilik)
            ->delete(route('aktivitas.destroy', $aktivitas))
            ->assertForbidden();

        $this->assertSame('asli', $aktivitas->fresh()->catatan);
        $this->assertModelExists($aktivitas);
    }

    public function test_tc_13_15_pengguna_lain_tidak_bisa_mengakses_aktivitas(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik, ['catatan' => 'asli']);
        $penyusup = Pengguna::factory()->create();

        $this->actingAs($penyusup)->get(route('aktivitas.show', $aktivitas))->assertForbidden();
        $this->actingAs($penyusup)
            ->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => 'diretas'])
            ->assertForbidden();
        $this->actingAs($penyusup)->delete(route('aktivitas.destroy', $aktivitas))->assertForbidden();

        $this->assertSame('asli', $aktivitas->fresh()->catatan);
        $this->assertModelExists($aktivitas);
    }

    public function test_tc_13_15_admin_pun_tidak_bisa_mengubah_atau_membatalkan_milik_orang_lain(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik);
        $admin = Pengguna::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => 'x'])->assertForbidden();
        $this->actingAs($admin)->delete(route('aktivitas.destroy', $aktivitas))->assertForbidden();
    }

    public function test_aktivitas_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->pemilik)->get('/aktivitas/99999')->assertNotFound();
    }

    public function test_tc_13_16_tanpa_login_diarahkan_ke_login(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik);

        $this->get('/pindai')->assertRedirect(route('login'));
        $this->get('/pindai/DEV-0001')->assertRedirect(route('login'));
        $this->post('/pindai', ['kode_perangkat' => 'DEV-0001'])->assertRedirect(route('login'));
        $this->get(route('aktivitas.show', $aktivitas))->assertRedirect(route('login'));
        $this->patch(route('aktivitas.catatan', $aktivitas), ['catatan' => 'x'])->assertRedirect(route('login'));
        $this->delete(route('aktivitas.destroy', $aktivitas))->assertRedirect(route('login'));

        $this->assertModelExists($aktivitas);
    }

    public function test_tc_13_16_tanpa_login_halaman_verifikasi_manual_diarahkan_ke_login(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik);

        $this->get('/admin/verifikasi')->assertRedirect(route('login'));
        $this->get(route('admin.verifikasi.show', $aktivitas))->assertRedirect(route('login'));
        $this->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])->assertRedirect(route('login'));
    }

    public function test_tc_13_16_pengguna_biasa_ditolak_403_di_verifikasi_manual(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik);
        VerifikasiSampah::factory()->ragu()->create(['id_aktivitas' => $aktivitas->id_aktivitas]);

        $this->actingAs($this->pemilik)->get('/admin/verifikasi')->assertForbidden();
        $this->actingAs($this->pemilik)->get(route('admin.verifikasi.show', $aktivitas))->assertForbidden();
        $this->actingAs($this->pemilik)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertForbidden();

        // Tidak ada perubahan dan tidak ada poin.
        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->fresh()->status_verifikasi);
        $this->assertDatabaseCount('transaksi_poin', 0);
    }

    public function test_tc_13_17_batalkan_lalu_kembali_tidak_mengubah_aktivitas(): void
    {
        $aktivitas = $this->aktivitasMilik($this->pemilik, ['catatan' => 'asli']);

        $html = $this->actingAs($this->pemilik)
            ->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->getContent();

        // "Kembali" hanya menutup modal (type=button), bukan mengirim form DELETE.
        $this->assertMatchesRegularExpression('/<button type="button"[^>]*>\s*Kembali\s*<\/button>/', $html);

        // Tanpa request DELETE, aktivitas tetap utuh dan halaman detail tetap bisa dibuka.
        $this->assertModelExists($aktivitas);
        $this->assertSame('asli', $aktivitas->fresh()->catatan);
        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->fresh()->status_verifikasi);
        $this->actingAs($this->pemilik)->get(route('aktivitas.show', $aktivitas))->assertOk();
    }
}
