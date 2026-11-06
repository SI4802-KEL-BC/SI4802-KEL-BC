<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\SesiPembuangan;
use App\Models\TransaksiPoin;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Pbi13\Concerns\SiapkanPerangkat;
use Tests\TestCase;

/** TC-13-03, 04, 11 (TC-13-05 dan 13 menyusul bersama panel admin di tahap 8). */
class AktivitasVerifikasiTest extends TestCase
{
    use RefreshDatabase;
    use SiapkanPerangkat;

    private KategoriSampah $plastik;

    private KategoriSampah $organik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanPerangkat();

        $this->plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
        $this->organik = KategoriSampah::factory()->create(['nama_kategori' => 'Organik']);
        AturanReward::factory()->create(['id_kategori' => $this->plastik->id_kategori, 'poin_per_satuan' => 10]);

        $this->lokasiMenerima($this->plastik); // lokasi uji hanya menerima Plastik
    }

    /** Jalankan alur lengkap perangkat: scan lalu kirim foto. */
    private function scanDanKirimFoto(): TestResponse
    {
        $token = $this->buatQr();
        $this->scan($token)->assertOk();

        return $this->kirimFoto($token);
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
        $token = $this->buatQr();
        $this->scan($token)->assertOk();

        $this->kirimFoto($token, null, UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'))
            ->assertStatus(422);

        $this->assertSame(0, AktivitasPemilahan::count());
        $this->assertSame(SesiPembuangan::AKTIF, SesiPembuangan::sole()->status_sesi);
    }

    public function test_tc_13_03_foto_untuk_qr_yang_belum_dipindai_ditolak(): void
    {
        $this->kirimFoto($this->buatQr())
            ->assertStatus(422)
            ->assertJsonPath('message', 'QR tidak valid atau kedaluwarsa');

        $this->assertSame(0, AktivitasPemilahan::count());
    }

    public function test_tc_13_03_foto_kedua_untuk_sesi_yang_sama_ditolak(): void
    {
        $this->mockIdentifikasi('plastic', 0.55);
        $token = $this->buatQr();
        $this->scan($token)->assertOk();

        $this->kirimFoto($token)->assertCreated();
        $this->kirimFoto($token)->assertStatus(422);

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
}
