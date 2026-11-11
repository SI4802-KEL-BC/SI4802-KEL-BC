<?php

namespace App\Services;

use App\Models\AktivitasPemilahan;
use App\Models\KategoriSampah;
use App\Models\VerifikasiSampah;
use Illuminate\Support\Facades\Log;

/**
 * Verifikasi otomatis tahap 1.
 *  - skor < ambang_skor (atau label tak terpetakan) => Ragu: status tetap Menunggu Validasi, masuk antrean Admin
 *  - kategori tidak diterima lokasi                 => Tidak Valid: Ditolak + alasan, poin 0
 *  - selain itu                                     => Valid: Terverifikasi + poin
 */
class VerifikasiTahap1
{
    public const ALASAN_KATEGORI = 'Kategori tidak sesuai tempat sampah';

    public function __construct(
        private readonly VerifikasiOtomatisService $identifikasi,
        private readonly PointService $poin,
    ) {}

    public function proses(AktivitasPemilahan $aktivitas): VerifikasiSampah
    {
        $hasil = $this->identifikasi->identifikasi($aktivitas->foto_bukti);
        $label = $hasil['label'];
        $skor = $hasil['skor'];

        $kategori = $this->kategoriDariLabel($label);

        if ($kategori) {
            $aktivitas->update(['id_kategori' => $kategori->id_kategori]);
        }

        if ($skor < config('pilahku.ambang_skor') || ! $kategori) {
            $verifikasi = VerifikasiSampah::verifikasiAwal($aktivitas, VerifikasiSampah::RAGU, $label, $skor);
        } elseif (! $aktivitas->lokasi->kategoriDiterima()->whereKey($kategori->id_kategori)->exists()) {
            $aktivitas->update([
                'status_verifikasi' => AktivitasPemilahan::DITOLAK,
                'alasan_penolakan' => self::ALASAN_KATEGORI,
                'poin_diperoleh' => 0,
            ]);
            $verifikasi = VerifikasiSampah::verifikasiAwal(
                $aktivitas, VerifikasiSampah::TIDAK_VALID, $label, $skor, self::ALASAN_KATEGORI
            );
        } else {
            $aktivitas->update(['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI]);
            $this->poin->berikanPoin($aktivitas);
            $verifikasi = VerifikasiSampah::verifikasiAwal($aktivitas, VerifikasiSampah::VALID, $label, $skor);
        }

        // Notifikasi hasil verifikasi belum punya spesifikasi/tabel; sementara dicatat di log.
        Log::info('Verifikasi tahap 1', [
            'id_aktivitas' => $aktivitas->id_aktivitas,
            'hasil' => $verifikasi->hasil,
            'status' => $aktivitas->status_verifikasi,
        ]);

        return $verifikasi;
    }

    private function kategoriDariLabel(string $label): ?KategoriSampah
    {
        $nama = config('pilahku.label_kategori.'.strtolower($label));

        return $nama ? KategoriSampah::whereRaw('LOWER(nama_kategori) = ?', [strtolower($nama)])->first() : null;
    }
}
