<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Models\TransaksiPoin;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Pbi13\Concerns\SiapkanPerangkat;
use Tests\TestCase;

/** TC-13-03, 04, 05, 11, 13 (+ aturan antrean verifikasi manual Admin). */
class AktivitasVerifikasiTest extends TestCase
{
    use RefreshDatabase;
    use SiapkanPerangkat;

    private KategoriSampah $plastik;

    private KategoriSampah $organik;

    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanPerangkat();
        $this->withoutVite();
        $this->admin = Pengguna::factory()->admin()->create();

        $this->plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
        $this->organik = KategoriSampah::factory()->create(['nama_kategori' => 'Organik']);
        AturanReward::factory()->create(['id_kategori' => $this->plastik->id_kategori, 'poin_per_satuan' => 10]);

        $this->lokasiMenerima($this->plastik); // lokasi uji hanya menerima Plastik
    }

    /** Alur lengkap: pengguna memindai QR tong, lalu kamera tong mengirim foto. */
    private function scanDanKirimFoto(): TestResponse
    {
        $this->pindai()->assertRedirect(route('pindai.index'));

        return $this->kirimFoto();
    }

    public function test_tc_13_03_foto_membuat_aktivitas_menunggu_validasi_dan_verifikasi_tahap_1(): void
    {
        $this->mockIdentifikasi('plastic', 0.55); // Ragu: status tetap Menunggu Validasi

        $this->scanDanKirimFoto()
            ->assertCreated()
            ->assertJsonPath('data.status_verifikasi', 'Menunggu Validasi')
            ->assertJsonPath('data.hasil_tahap_1', 'Ragu');

        $sesi = SesiPembuangan::sole();
        $aktivitas = AktivitasPemilahan::sole();

        $this->assertSame($sesi->id_sesi, $aktivitas->id_sesi);
        $this->assertSame($this->pengguna->id_pengguna, $aktivitas->id_pengguna);
        $this->assertSame($this->lokasi->id_lokasi, $aktivitas->id_lokasi);
        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->status_verifikasi);
        $this->assertSame(0, $aktivitas->poin_diperoleh);
        $this->assertSame(SesiPembuangan::SELESAI, $sesi->fresh()->status_sesi);
        Storage::disk('public')->assertExists($aktivitas->foto_bukti);

        $verifikasi = VerifikasiSampah::sole();
        $this->assertSame($aktivitas->id_aktivitas, $verifikasi->id_aktivitas);
        $this->assertSame(VerifikasiSampah::TAHAP_OTOMATIS, $verifikasi->tahap);
        $this->assertNull($verifikasi->id_verifikator);
        $this->assertSame(VerifikasiSampah::RAGU, $verifikasi->hasil);
        $this->assertSame('plastic', $verifikasi->label_terdeteksi);
        $this->assertEquals(0.55, $verifikasi->skor_keyakinan);
    }

    public function test_tc_13_03_foto_bukan_gambar_ditolak_tanpa_aktivitas(): void
    {
        $this->pindai()->assertRedirect();

        $this->kirimFoto(null, null, UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'))
            ->assertStatus(422);

        $this->assertSame(0, AktivitasPemilahan::count());
        $this->assertSame(SesiPembuangan::AKTIF, SesiPembuangan::sole()->status_sesi);
    }

    public function test_tc_13_03_foto_untuk_tong_yang_belum_dipindai_ditolak(): void
    {
        $this->kirimFoto()
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tidak ada sesi aktif di tong ini. Pindai QR tong terlebih dahulu');

        $this->assertSame(0, AktivitasPemilahan::count());
    }

    public function test_tc_13_03_foto_kedua_untuk_sesi_yang_sama_ditolak(): void
    {
        $this->mockIdentifikasi('plastic', 0.55);
        $this->pindai()->assertRedirect();

        $this->kirimFoto()->assertCreated();
        $this->kirimFoto()->assertStatus(422); // sesi sudah Selesai: tidak ada sesi aktif lagi

        $this->assertSame(1, AktivitasPemilahan::count());
    }

    public function test_tc_13_04_skor_di_atas_ambang_dan_kategori_diterima_terverifikasi_dengan_poin(): void
    {
        $this->mockIdentifikasi('plastic', 0.92);

        $this->scanDanKirimFoto()
            ->assertCreated()
            ->assertJsonPath('data.status_verifikasi', 'Terverifikasi')
            ->assertJsonPath('data.hasil_tahap_1', 'Valid');

        $aktivitas = AktivitasPemilahan::sole();
        $this->assertSame(AktivitasPemilahan::TERVERIFIKASI, $aktivitas->status_verifikasi);
        $this->assertSame($this->plastik->id_kategori, $aktivitas->id_kategori);
        $this->assertGreaterThan(0, $aktivitas->poin_diperoleh);
        $this->assertSame(10, $aktivitas->poin_diperoleh);
        $this->assertNull($aktivitas->alasan_penolakan);

        $this->assertSame(1, TransaksiPoin::count());
        $transaksi = TransaksiPoin::sole();
        $this->assertSame($aktivitas->id_aktivitas, $transaksi->id_aktivitas);
        $this->assertSame($this->pengguna->id_pengguna, $transaksi->id_pengguna);
        $this->assertSame(10, $transaksi->jumlah_poin);

        $verifikasi = VerifikasiSampah::sole();
        $this->assertSame(VerifikasiSampah::VALID, $verifikasi->hasil);
        $this->assertSame('plastic', $verifikasi->label_terdeteksi);
        $this->assertEquals(0.92, $verifikasi->skor_keyakinan);
    }

    public function test_tc_13_04_skor_sama_dengan_ambang_dianggap_valid(): void
    {
        $this->mockIdentifikasi('plastic', 0.70); // ambang_skor default 0.70: hanya skor < ambang yang Ragu

        $this->scanDanKirimFoto()
            ->assertCreated()
            ->assertJsonPath('data.hasil_tahap_1', 'Valid');
    }

    public function test_skor_tepat_di_bawah_ambang_menjadi_ragu_walau_kategori_salah(): void
    {
        $this->mockIdentifikasi('organik', 0.69); // kategori tak diterima, tapi skor rendah => Ragu dulu

        $this->scanDanKirimFoto()
            ->assertCreated()
            ->assertJsonPath('data.status_verifikasi', 'Menunggu Validasi')
            ->assertJsonPath('data.hasil_tahap_1', 'Ragu');

        $this->assertSame(0, TransaksiPoin::count());
    }

    public function test_label_tak_terpetakan_menjadi_ragu_tanpa_kategori(): void
    {
        $this->mockIdentifikasi('kaca', 0.95);

        $this->scanDanKirimFoto()
            ->assertCreated()
            ->assertJsonPath('data.status_verifikasi', 'Menunggu Validasi')
            ->assertJsonPath('data.hasil_tahap_1', 'Ragu');

        $this->assertNull(AktivitasPemilahan::sole()->id_kategori);
    }

    public function test_tc_13_11_kategori_tidak_diterima_lokasi_ditolak_tanpa_poin(): void
    {
        $this->mockIdentifikasi('organik', 0.90); // lokasi hanya menerima Plastik

        $this->scanDanKirimFoto()
            ->assertCreated()
            ->assertJsonPath('data.status_verifikasi', 'Ditolak')
            ->assertJsonPath('data.hasil_tahap_1', 'Tidak Valid');

        $aktivitas = AktivitasPemilahan::sole();
        $this->assertSame(AktivitasPemilahan::DITOLAK, $aktivitas->status_verifikasi);
        $this->assertSame('Kategori tidak sesuai tempat sampah', $aktivitas->alasan_penolakan);
        $this->assertSame($this->organik->id_kategori, $aktivitas->id_kategori);
        $this->assertSame(0, $aktivitas->poin_diperoleh);

        // Ditolak = poin 0 dan tidak ada transaksi (termasuk pengurangan poin).
        $this->assertSame(0, TransaksiPoin::count());

        $verifikasi = VerifikasiSampah::sole();
        $this->assertSame(VerifikasiSampah::TIDAK_VALID, $verifikasi->hasil);
        $this->assertSame('Kategori tidak sesuai tempat sampah', $verifikasi->keterangan);
    }

    /** Aktivitas milik pengguna uji yang masuk antrean manual (tahap 1 = Ragu). */
    private function aktivitasRagu(array $state = [], ?KategoriSampah $kategori = null): AktivitasPemilahan
    {
        $sesi = SesiPembuangan::factory()->create([
            'id_pengguna' => $this->pengguna->id_pengguna,
            'id_lokasi' => $this->lokasi->id_lokasi,
            'status_sesi' => SesiPembuangan::SELESAI,
        ]);
        $aktivitas = AktivitasPemilahan::factory()->create($state + [
            'id_sesi' => $sesi->id_sesi,
            'id_kategori' => ($kategori ?? $this->plastik)->id_kategori,
        ]);
        VerifikasiSampah::factory()->ragu(0.55)->create(['id_aktivitas' => $aktivitas->id_aktivitas]);

        return $aktivitas;
    }

    public function test_tc_13_05_skor_rendah_masuk_antrean_lalu_admin_menyetujui(): void
    {
        $this->mockIdentifikasi('plastic', 0.55);
        $this->scanDanKirimFoto()->assertCreated()->assertJsonPath('data.hasil_tahap_1', 'Ragu');
        $aktivitas = AktivitasPemilahan::sole();

        // Muncul di /admin/verifikasi.
        $this->actingAs($this->admin)
            ->get('/admin/verifikasi')
            ->assertOk()
            ->assertSee('Dalam antrean')
            ->assertSee($this->pengguna->nama_lengkap)
            ->assertSee('plastic')
            ->assertSee('55%')
            ->assertSee(route('admin.verifikasi.show', $aktivitas), false);

        // Detail verifikasi: foto, label, skor, tombol Setujui / Tolak.
        $this->actingAs($this->admin)
            ->get(route('admin.verifikasi.show', $aktivitas))
            ->assertOk()
            ->assertSee('storage/'.$aktivitas->foto_bukti, false)
            ->assertSee('plastic')
            ->assertSee('0.55')
            ->assertSee('Setujui')
            ->assertSee('Tolak');

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertRedirect(route('admin.verifikasi.index'))
            ->assertSessionHas('status');

        $aktivitas->refresh();
        $this->assertSame(AktivitasPemilahan::TERVERIFIKASI, $aktivitas->status_verifikasi);
        $this->assertSame(10, $aktivitas->poin_diperoleh);
        $this->assertSame(1, TransaksiPoin::count());
        $this->assertSame($aktivitas->id_aktivitas, TransaksiPoin::sole()->id_aktivitas);

        // Verifikasi tahap 2 tercatat dengan id_verifikator = Admin; tahap 1 tetap ada.
        $this->assertSame(2, VerifikasiSampah::count());
        $tahap2 = VerifikasiSampah::where('tahap', VerifikasiSampah::TAHAP_MANUAL)->sole();
        $this->assertSame($this->admin->id_pengguna, $tahap2->id_verifikator);
        $this->assertSame(VerifikasiSampah::VALID, $tahap2->hasil);
        $this->assertSame(VerifikasiSampah::RAGU, VerifikasiSampah::where('tahap', 1)->sole()->hasil);

        // Keluar dari antrean.
        $this->actingAs($this->admin)->get('/admin/verifikasi')->assertDontSee('data-antrean', false);
    }

    public function test_tc_13_05_admin_menolak_dengan_alasan_poin_nol_dan_tahap_2_tercatat(): void
    {
        $aktivitas = $this->aktivitasRagu();

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'tolak', 'alasan' => 'Foto tidak jelas'])
            ->assertRedirect(route('admin.verifikasi.index'));

        $aktivitas->refresh();
        $this->assertSame(AktivitasPemilahan::DITOLAK, $aktivitas->status_verifikasi);
        $this->assertSame('Foto tidak jelas', $aktivitas->alasan_penolakan);
        $this->assertSame(0, $aktivitas->poin_diperoleh);
        $this->assertSame(0, TransaksiPoin::count()); // tidak ada poin maupun pengurangan poin

        $tahap2 = VerifikasiSampah::where('tahap', VerifikasiSampah::TAHAP_MANUAL)->sole();
        $this->assertSame($this->admin->id_pengguna, $tahap2->id_verifikator);
        $this->assertSame(VerifikasiSampah::TIDAK_VALID, $tahap2->hasil);
        $this->assertSame('Foto tidak jelas', $tahap2->keterangan);
    }

    public function test_tc_13_13_admin_menolak_tanpa_alasan_ditolak_validasi(): void
    {
        $aktivitas = $this->aktivitasRagu();

        foreach ([null, '', '   '] as $alasanKosong) {
            $this->actingAs($this->admin)
                ->from(route('admin.verifikasi.show', $aktivitas))
                ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'tolak', 'alasan' => $alasanKosong])
                ->assertRedirect(route('admin.verifikasi.show', $aktivitas))
                ->assertSessionHasErrors(['alasan' => 'Alasan wajib diisi']);
        }

        $aktivitas->refresh();
        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->status_verifikasi);
        $this->assertNull($aktivitas->alasan_penolakan);
        $this->assertSame(0, VerifikasiSampah::where('tahap', VerifikasiSampah::TAHAP_MANUAL)->count());

        // Pesan tampil di halaman detail setelah gagal validasi.
        $this->actingAs($this->admin)
            ->from(route('admin.verifikasi.show', $aktivitas))
            ->followingRedirects()
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'tolak'])
            ->assertSee('Alasan wajib diisi');
    }

    public function test_alasan_tidak_wajib_saat_menyetujui(): void
    {
        $aktivitas = $this->aktivitasRagu();

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertSessionHasNoErrors();

        $this->assertSame(AktivitasPemilahan::TERVERIFIKASI, $aktivitas->fresh()->status_verifikasi);
    }

    public function test_keputusan_tidak_dikenal_ditolak(): void
    {
        $aktivitas = $this->aktivitasRagu();

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'hapus'])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->fresh()->status_verifikasi);
    }

    public function test_antrean_hanya_menampilkan_hasil_tahap_1_ragu(): void
    {
        $ragu = $this->aktivitasRagu();

        // Valid (Terverifikasi otomatis), Tidak Valid (Ditolak otomatis), dan Menunggu tanpa hasil tahap 1.
        $valid = $this->aktivitasRagu(['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI]);
        $valid->verifikasi()->update(['hasil' => VerifikasiSampah::VALID]);
        $tidakValid = $this->aktivitasRagu(['status_verifikasi' => AktivitasPemilahan::DITOLAK]);
        $tidakValid->verifikasi()->update(['hasil' => VerifikasiSampah::TIDAK_VALID]);
        $tanpaHasil = $this->aktivitasRagu();
        $tanpaHasil->verifikasi()->delete();

        $this->actingAs($this->admin)
            ->get('/admin/verifikasi')
            ->assertOk()
            ->assertSee(route('admin.verifikasi.show', $ragu), false)
            ->assertDontSee(route('admin.verifikasi.show', $valid), false)
            ->assertDontSee(route('admin.verifikasi.show', $tidakValid), false)
            ->assertDontSee(route('admin.verifikasi.show', $tanpaHasil), false);

        $this->assertSame([$ragu->id_aktivitas], AktivitasPemilahan::antreanManual()->pluck('id_aktivitas')->all());
    }

    public function test_antrean_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/verifikasi')
            ->assertOk()
            ->assertSee('Antrean kosong');
    }

    public function test_aktivitas_yang_bukan_antrean_tidak_bisa_dilihat_atau_diproses_admin(): void
    {
        $valid = $this->aktivitasRagu(['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI]);
        $valid->verifikasi()->update(['hasil' => VerifikasiSampah::VALID]);

        $this->actingAs($this->admin)->get(route('admin.verifikasi.show', $valid))->assertNotFound();
        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $valid), ['keputusan' => 'tolak', 'alasan' => 'x'])
            ->assertNotFound();

        $this->assertSame(AktivitasPemilahan::TERVERIFIKASI, $valid->fresh()->status_verifikasi);
    }

    public function test_aktivitas_yang_sudah_diproses_admin_tidak_bisa_diproses_lagi_dan_poin_tidak_ganda(): void
    {
        $aktivitas = $this->aktivitasRagu();

        $this->actingAs($this->admin)->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui']);
        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertNotFound();

        $this->assertSame(1, TransaksiPoin::count());
        $this->assertSame(10, $aktivitas->fresh()->poin_diperoleh);
    }

    public function test_aktivitas_yang_dibatalkan_pengguna_hilang_dari_antrean_dan_tidak_bisa_diproses(): void
    {
        $aktivitas = $this->aktivitasRagu();

        $this->actingAs($this->pengguna)->delete(route('aktivitas.destroy', $aktivitas))->assertRedirect();

        $this->actingAs($this->admin)->get('/admin/verifikasi')
            ->assertDontSee(route('admin.verifikasi.show', $aktivitas), false);
        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertNotFound();
        $this->assertSame(0, TransaksiPoin::count());
    }

    public function test_aktivitas_tanpa_kategori_tidak_bisa_disetujui_tetapi_bisa_ditolak(): void
    {
        $this->mockIdentifikasi('kaca', 0.95); // label tak terpetakan => Ragu tanpa kategori
        $this->scanDanKirimFoto()->assertCreated();
        $aktivitas = AktivitasPemilahan::sole();
        $this->assertNull($aktivitas->id_kategori);

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'setujui'])
            ->assertSessionHasErrors('keputusan');
        $this->assertSame(AktivitasPemilahan::MENUNGGU, $aktivitas->fresh()->status_verifikasi);
        $this->assertSame(0, VerifikasiSampah::where('tahap', VerifikasiSampah::TAHAP_MANUAL)->count());

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.proses', $aktivitas), ['keputusan' => 'tolak', 'alasan' => 'Jenis sampah tidak dikenali'])
            ->assertSessionHasNoErrors();
        $this->assertSame(AktivitasPemilahan::DITOLAK, $aktivitas->fresh()->status_verifikasi);
    }
}
