<?php

namespace Tests\Feature\PBI13;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\SesiPembuangan;
use App\Models\VerifikasiSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PBI13\Concerns\SiapkanPerangkat;
use Tests\TestCase;

/**
 * SCRUM-36 · PBI-13 Mengelola Aktivitas Pemilahan
 *
 * Use Case  : Mengelola Aktivitas Pemilahan (UC-13)
 * Aktor     : User, Perangkat Tong (pengirim foto), Admin (verifikasi manual & cetak QR)
 * Awal      : Pengguna sudah login dan berada di depan tong yang memiliki QR
 * Akhir     : Aktivitas tersimpan (Terverifikasi / Ditolak / Menunggu Validasi),
 *             atau perubahan dan pembatalan berhasil
 *
 * Test case TC-13-01 s.d. TC-13-17 ada di QrSessionTest, AktivitasVerifikasiTest,
 * AktivitasPenggunaTest, dan AktivitasPolicyTest. File ini menguji alur use case dari awal sampai akhir.
 */
class MengelolaAktivitasPemilahanTest extends TestCase
{
    use RefreshDatabase;
    use SiapkanPerangkat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->siapkanPerangkat();

        $plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik', 'status' => 'aktif']);
        AturanReward::factory()->create(['id_kategori' => $plastik->id_kategori, 'poin_per_satuan' => 10]);
        $this->lokasiMenerima($plastik);
        $this->lokasi->update(['nama_lokasi' => 'Kantin TULT']);
    }

    /** Skenario Normal: buka Pindai QR → pindai tong → foto dari tong → aktivitas tersimpan → buka detail. */
    public function test_use_case_skenario_normal(): void
    {
        // 1–2. Membuka Pindai QR → pemindai dan input kode tong tampil.
        $this->actingAs($this->pengguna)->get(route('pindai.index'))
            ->assertOk()
            ->assertSee('Pindai QR Tong Sampah')
            ->assertSee('name="kode_perangkat"', false);

        // 3–4. Memindai QR tong → tong divalidasi dan sesi pembuangan dibuat.
        $this->pindai()->assertRedirect(route('pindai.index'))
            ->assertSessionHas('status', 'QR tong berhasil dipindai di Kantin TULT.');
        $sesi = SesiPembuangan::sole();
        $this->assertSame(SesiPembuangan::AKTIF, $sesi->status_sesi);

        // 5–6. Perangkat mengirim foto → aktivitas tersimpan dan verifikasi otomatis berjalan.
        $this->mockIdentifikasi('plastic', 0.92);
        $this->kirimFoto()->assertSuccessful();

        $aktivitas = AktivitasPemilahan::sole();
        $this->assertSame(SesiPembuangan::SELESAI, $sesi->fresh()->status_sesi);
        $this->assertSame(AktivitasPemilahan::TERVERIFIKASI, $aktivitas->status_verifikasi);
        $this->assertSame(1, VerifikasiSampah::where('id_aktivitas', $aktivitas->id_aktivitas)->where('tahap', 1)->count());

        // 7–8. Membuka detail aktivitas → hasil verifikasi tampil.
        $this->actingAs($this->pengguna)->get(route('aktivitas.show', $aktivitas))
            ->assertOk()
            ->assertSee('Terverifikasi')
            ->assertSee('Kantin TULT')
            ->assertSee('+10');
    }

    /** Skenario Gagal: QR tidak dikenal / tong nonaktif ditolak; tanpa foto sampai batas waktu → Kedaluwarsa. */
    public function test_use_case_skenario_gagal(): void
    {
        // 1–2. Memindai QR yang tidak dikenal → ditolak dengan pesan.
        $this->pindai(kodePerangkat: 'TIDAK-ADA')->assertSessionHasErrors('kode_perangkat');

        // 1–2. Memindai tong nonaktif → ditolak dengan pesan.
        $this->lokasi->update(['status' => 'nonaktif']);
        $this->pindai()->assertSessionHasErrors(['kode_perangkat' => 'Tong sampah ini sedang tidak aktif.']);
        $this->assertSame(0, SesiPembuangan::count());

        // 3–4. Tidak membuang sampah sampai batas waktu → sesi Kedaluwarsa tanpa aktivitas.
        $this->lokasi->update(['status' => 'aktif']);
        $this->pindai()->assertSessionHasNoErrors();
        $this->travel(config('pilahku.session_ttl') + 1)->seconds();
        $this->artisan('sesi:kedaluwarsa')->assertSuccessful();

        $this->assertSame(SesiPembuangan::KEDALUWARSA, SesiPembuangan::sole()->status_sesi);
        $this->assertSame(0, AktivitasPemilahan::count());
    }
}
