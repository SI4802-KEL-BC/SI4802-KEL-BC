<?php

namespace App\Services;

use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pengguna memindai QR yang tertempel di tong sampah (URL tetap /pindai/{kode_perangkat}).
 * Memindai membuat sesi_pembuangan Aktif atas nama pengguna di tong itu; kamera tong lalu mengirim foto
 * ke /api/perangkat/foto untuk sesi aktif di tongnya.
 *
 * Aturan:
 *  - tong harus berstatus aktif;
 *  - satu pengguna hanya punya satu sesi aktif (memindai tong yang sama lagi = melanjutkan sesi yang ada);
 *  - satu tong hanya melayani satu sesi aktif pada satu waktu (agar foto dari tong jelas milik siapa).
 */
class PindaiTongService
{
    /** @return array{ok: bool, pesan: string, sesi: ?SesiPembuangan, dilanjutkan: bool} */
    public function mulai(Pengguna $pengguna, LokasiTempatSampah $lokasi): array
    {
        if ($lokasi->status !== 'aktif') {
            return $this->gagal('Tong sampah ini sedang tidak aktif.');
        }

        return DB::transaction(function () use ($pengguna, $lokasi) {
            // Kunci baris tong agar dua pengguna yang memindai bersamaan tidak sama-sama mendapat sesi.
            LokasiTempatSampah::whereKey($lokasi->getKey())->lockForUpdate()->first();

            $sesiBerlaku = fn () => SesiPembuangan::with('lokasi')
                ->where('status_sesi', SesiPembuangan::AKTIF)
                ->where('kedaluwarsa_pada', '>', now());

            $milikSaya = $sesiBerlaku()->where('id_pengguna', $pengguna->id_pengguna)->latest('waktu_scan')->first();

            if ($milikSaya) {
                if ((int) $milikSaya->id_lokasi === (int) $lokasi->id_lokasi) {
                    return ['ok' => true, 'pesan' => "Sesi di {$lokasi->nama_lokasi} dilanjutkan.", 'sesi' => $milikSaya, 'dilanjutkan' => true];
                }

                return $this->gagal("Kamu masih punya sesi aktif di {$milikSaya->lokasi->nama_lokasi}. Selesaikan atau tunggu sampai berakhir.");
            }

            if ($sesiBerlaku()->where('id_lokasi', $lokasi->id_lokasi)->exists()) {
                return $this->gagal('Tong ini sedang dipakai pengguna lain. Coba lagi beberapa menit lagi.');
            }

            // QR tong bersifat tetap, jadi tidak ada token sekali pakai; token_qr hanya pengenal unik sesi.
            $sesi = SesiPembuangan::buatSesi($pengguna->id_pengguna, $lokasi->id_lokasi, hash('sha256', Str::uuid()->toString()));
            $sesi->setRelation('lokasi', $lokasi);

            return ['ok' => true, 'pesan' => "QR tong berhasil dipindai di {$lokasi->nama_lokasi}.", 'sesi' => $sesi, 'dilanjutkan' => false];
        });
    }

    /** Alamat yang dikodekan di QR tong. Dasarnya bisa diganti lewat PILAHKU_QR_BASE_URL (mis. IP LAN untuk uji dari HP). */
    public function urlQr(LokasiTempatSampah $lokasi): string
    {
        $dasar = rtrim((string) (config('pilahku.qr_base_url') ?: url('/')), '/');

        return $dasar.'/pindai/'.rawurlencode($lokasi->kode_perangkat);
    }

    private function gagal(string $pesan): array
    {
        return ['ok' => false, 'pesan' => $pesan, 'sesi' => null, 'dilanjutkan' => false];
    }
}
