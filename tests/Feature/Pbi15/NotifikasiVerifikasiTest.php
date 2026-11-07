<?php

namespace Tests\Feature\Pbi15;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\Pengguna;
use App\Notifications\HasilVerifikasiNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Pbi13\Concerns\SiapkanPerangkat;
use Tests\TestCase;

/** PBI-15 · TC-15-05: notifikasi hasil verifikasi (otomatis & Admin), tepat sekali, tidak saat Menunggu. */
class NotifikasiVerifikasiTest extends TestCase
{
    use RefreshDatabase;
    use SiapkanPerangkat;

    private KategoriSampah $plastik;

    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanPerangkat();
        $this->withoutVite();
        $this->admin = Pengguna::factory()->admin()->create(['nama_lengkap' => 'Admin Uji']);

        $this->plastik = KategoriSampah::factory()->create(['nama_kategori' => 'Plastik']);
        AturanReward::factory()->create(['id_kategori' => $this->plastik->id_kategori, 'poin_per_satuan' => 10]);
        $this->lokasiMenerima($this->plastik);
    }

    /** Alur nyata: pindai QR tong, lalu kamera tong mengirim foto. Mengembalikan id aktivitas. */
    private function catat(): int
    {
        $this->pindai()->assertRedirect(route('pindai.index'));

        return $this->kirimFoto()->assertCreated()->json('data.id_aktivitas');
    }

    private function adminMemutuskan(int $id, string $keputusan, ?string $alasan = null): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/verifikasi/'.$id, ['keputusan' => $keputusan, 'alasan' => $alasan])
            ->assertRedirect(route('admin.verifikasi.index'));
    }

    public function test_tc_15_05_verifikasi_otomatis_valid_mengirim_satu_notifikasi_ke_pemilik(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.92);

        $id = $this->catat();

        Notification::assertSentToTimes($this->pengguna, HasilVerifikasiNotification::class, 1);
        Notification::assertSentTo($this->pengguna, HasilVerifikasiNotification::class,
            fn (HasilVerifikasiNotification $n, array $kanal) => $n->aktivitas->id_aktivitas === $id
                && $n->aktivitas->status_verifikasi === 'Terverifikasi'
                && $kanal === ['mail']);
        Notification::assertNotSentTo($this->admin, HasilVerifikasiNotification::class);
        Notification::assertCount(1);
    }

    public function test_verifikasi_otomatis_tidak_valid_mengirim_notifikasi_ditolak(): void
    {
        Notification::fake();
        $organik = KategoriSampah::factory()->create(['nama_kategori' => 'Organik']);
        $this->mockIdentifikasi('organik', 0.9); // lokasi uji hanya menerima Plastik

        $this->catat();

        Notification::assertSentToTimes($this->pengguna, HasilVerifikasiNotification::class, 1);
        Notification::assertSentTo($this->pengguna, HasilVerifikasiNotification::class,
            fn ($n) => $n->aktivitas->status_verifikasi === 'Ditolak'
                && $n->aktivitas->alasan_penolakan === 'Kategori tidak sesuai tempat sampah');
    }

    public function test_hasil_ragu_tidak_mengirim_notifikasi_karena_masih_menunggu(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.55);

        $id = $this->catat();

        $this->assertSame('Menunggu Validasi', AktivitasPemilahan::find($id)->status_verifikasi);
        Notification::assertNothingSent();
    }

    public function test_admin_menolak_mengirim_satu_notifikasi_ke_pemilik_bukan_ke_admin(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.55);
        $id = $this->catat();
        Notification::assertNothingSent();

        $this->adminMemutuskan($id, 'tolak', 'Foto tidak jelas');

        Notification::assertSentToTimes($this->pengguna, HasilVerifikasiNotification::class, 1);
        Notification::assertSentTo($this->pengguna, HasilVerifikasiNotification::class,
            fn ($n) => $n->aktivitas->status_verifikasi === 'Ditolak' && $n->aktivitas->alasan_penolakan === 'Foto tidak jelas');
        Notification::assertNotSentTo($this->admin, HasilVerifikasiNotification::class);
        Notification::assertCount(1);
    }

    public function test_admin_menyetujui_mengirim_satu_notifikasi_terverifikasi(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.55);
        $id = $this->catat();

        $this->adminMemutuskan($id, 'setujui');

        Notification::assertSentToTimes($this->pengguna, HasilVerifikasiNotification::class, 1);
        Notification::assertSentTo($this->pengguna, HasilVerifikasiNotification::class,
            fn ($n) => $n->aktivitas->status_verifikasi === 'Terverifikasi');
    }

    public function test_keputusan_admin_yang_gagal_validasi_tidak_mengirim_notifikasi(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.55);
        $id = $this->catat();

        $this->actingAs($this->admin)->post('/admin/verifikasi/'.$id, ['keputusan' => 'tolak'])
            ->assertSessionHasErrors('alasan');

        Notification::assertNothingSent();
    }

    public function test_ubah_catatan_saat_menunggu_tidak_mengirim_notifikasi(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.55);
        $id = $this->catat();

        $this->actingAs($this->pengguna)
            ->patch('/aktivitas/'.$id.'/catatan', ['catatan' => 'botol air mineral'])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_isi_email_terverifikasi_memuat_kode_status_poin_dan_tautan(): void
    {
        Notification::fake();
        $this->mockIdentifikasi('plastic', 0.92);
        $id = $this->catat();
        $akt = AktivitasPemilahan::find($id);

        $mail = (new HasilVerifikasiNotification($akt))->toMail($this->pengguna);
        $teks = $mail->render()->toHtml();
        $kode = 'AKT-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);

        $this->assertSame("Hasil verifikasi aktivitas {$kode}: Terverifikasi", $mail->subject);
        $this->assertStringContainsString($kode, $teks);
        $this->assertStringContainsString('Terverifikasi', $teks);
        $this->assertStringContainsString('+10', $teks);
        $this->assertStringContainsString('Lihat detail', $teks);
        $this->assertSame(route('aktivitas.show', $akt), $mail->actionUrl);
    }

    public function test_isi_email_ditolak_memuat_alasan_dan_tanpa_poin(): void
    {
        $akt = AktivitasPemilahan::factory()->ditolak('Foto tidak jelas')->create(['id_pengguna' => $this->pengguna->id_pengguna]);

        $teks = (new HasilVerifikasiNotification($akt))->toMail($this->pengguna)->render()->toHtml();

        $this->assertStringContainsString('Ditolak', $teks);
        $this->assertStringContainsString('Foto tidak jelas', $teks);
        $this->assertStringContainsString('tidak mendapat poin', $teks);
        $this->assertStringNotContainsString('Poin yang kamu dapat', $teks);
    }

    public function test_notifikasi_dikirim_ke_email_kampus_pemilik(): void
    {
        $this->assertSame($this->pengguna->email_kampus, $this->pengguna->routeNotificationFor('mail'));
    }

    public function test_gagal_kirim_email_tidak_membatalkan_hasil_verifikasi(): void
    {
        $this->mock(Dispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->andThrow(new \RuntimeException('SMTP mati'));
            $mock->shouldReceive('sendNow')->andThrow(new \RuntimeException('SMTP mati'));
        });
        $this->mockIdentifikasi('plastic', 0.92);

        $id = $this->catat();

        $akt = AktivitasPemilahan::find($id);
        $this->assertSame('Terverifikasi', $akt->status_verifikasi);
        $this->assertSame(10, $akt->poin_diperoleh);
        $this->assertNotNull($akt->transaksiPoin);
    }
}
