<?php

namespace Tests\Browser\Concerns;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Models\TransaksiPoin;
use App\Models\VerifikasiSampah;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Data bersama untuk test browser (Laravel Dusk). Data ditulis langsung ke database pilahku_dusk
 * yang juga dibaca situs lokal selama `php artisan dusk` berjalan.
 */
trait DataUji
{
    /** Kata sandi semua akun uji (sama dengan PenggunaFactory). */
    protected const SANDI = 'password';

    protected function pengguna(array $atribut = []): Pengguna
    {
        return Pengguna::factory()->create($atribut + ['nama_lengkap' => 'Pengguna Dusk']);
    }

    protected function admin(array $atribut = []): Pengguna
    {
        return Pengguna::factory()->admin()->create($atribut + ['nama_lengkap' => 'Admin Dusk']);
    }

    protected function kategoriPlastik(): KategoriSampah
    {
        $kategori = KategoriSampah::firstOrCreate(
            ['nama_kategori' => 'Plastik'],
            ['kode' => 'PLS', 'warna_label' => '#3b82f6', 'status' => 'aktif'],
        );
        AturanReward::firstOrCreate(
            ['id_kategori' => $kategori->id_kategori],
            [
                'kode_aturan' => 'RWD-'.str_pad((string) $kategori->id_kategori, 2, '0', STR_PAD_LEFT),
                'nama_aturan' => 'Pemilahan Plastik',
                'jenis_aturan' => 'Penambahan',
                'poin_per_satuan' => 10,
                'satuan' => 'per item',
                'syarat' => ['aktivitas_terverifikasi'],
                'berlaku_mulai' => now()->toDateString(),
                'status' => 'aktif',
            ],
        );

        return $kategori;
    }

    /** Tong aktif yang menerima Plastik (hasil verifikasi otomatis palsu = plastic, skor 0,92). */
    protected function tongPlastik(array $atribut = []): LokasiTempatSampah
    {
        $lokasi = LokasiTempatSampah::factory()->create($atribut + [
            'nama_lokasi' => 'Kantin Dusk',
            'kode_perangkat' => 'DUSK-0001',
            'latitude' => -6.9747,
            'longitude' => 107.6304,
        ]);
        $lokasi->kategori()->sync([$this->kategoriPlastik()->id_kategori]);

        return $lokasi;
    }

    /** Aktivitas milik pengguna dengan status tertentu, lengkap dengan hasil verifikasi tahap 1. */
    protected function aktivitas(Pengguna $pemilik, string $status = AktivitasPemilahan::MENUNGGU, array $atribut = []): AktivitasPemilahan
    {
        $lokasi = LokasiTempatSampah::first() ?? $this->tongPlastik();
        $sesi = SesiPembuangan::factory()->create([
            'id_pengguna' => $pemilik->id_pengguna,
            'id_lokasi' => $lokasi->id_lokasi,
            'status_sesi' => SesiPembuangan::SELESAI,
        ]);

        $aktivitas = AktivitasPemilahan::factory()->create($atribut + [
            'id_sesi' => $sesi->id_sesi,
            'id_kategori' => $this->kategoriPlastik()->id_kategori,
            'status_verifikasi' => $status,
            'poin_diperoleh' => $status === AktivitasPemilahan::TERVERIFIKASI ? 10 : 0,
            'alasan_penolakan' => $status === AktivitasPemilahan::DITOLAK ? 'Kategori tidak sesuai tempat sampah' : null,
        ]);

        $verifikasi = VerifikasiSampah::factory();
        if ($status === AktivitasPemilahan::MENUNGGU) {
            $verifikasi = $verifikasi->ragu();
        }
        $verifikasi->create(['id_aktivitas' => $aktivitas->id_aktivitas]);

        // Poin yang tampil di riwayat & detail dibaca dari transaksi_poin.
        if ($status === AktivitasPemilahan::TERVERIFIKASI) {
            TransaksiPoin::factory()->create([
                'id_pengguna' => $pemilik->id_pengguna,
                'id_aktivitas' => $aktivitas->id_aktivitas,
                'jumlah_poin' => 10,
            ]);
        }

        return $aktivitas;
    }

    protected function kodeAktivitas(AktivitasPemilahan $aktivitas): string
    {
        return 'AKT-'.str_pad((string) $aktivitas->id_aktivitas, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Kamera tong mengirim foto ke API perangkat situs lokal, persis seperti perangkat sungguhan.
     * Token sama dengan PILAHKU_DEVICE_TOKENS di .env.dusk.local.
     */
    protected function kirimFotoDariTong(string $kodePerangkat): Response
    {
        $gambar = imagecreatetruecolor(64, 64);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 59, 130, 246));
        ob_start();
        imagepng($gambar);
        $png = (string) ob_get_clean();

        return Http::withHeaders(['X-Device-Token' => 'dusk-token', 'Accept' => 'application/json'])
            ->attach('foto', $png, 'sampah.png')
            ->post(rtrim(config('app.url'), '/').'/api/perangkat/foto', ['kode_perangkat' => $kodePerangkat]);
    }
}
