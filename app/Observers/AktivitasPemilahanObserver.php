<?php

namespace App\Observers;

use App\Models\AktivitasPemilahan;
use App\Notifications\HasilVerifikasiNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PBI-15: SATU-SATUNYA tempat notifikasi hasil verifikasi dipicu.
 * Verifikasi otomatis (VerifikasiTahap1) dan Admin (VerifikasiController) sama-sama mengubah
 * status_verifikasi lewat model ini, jadi keduanya tercakup tanpa kode ganda.
 *
 * Terkirim tepat sekali: hanya saat status BERUBAH menjadi Terverifikasi/Ditolak (status terkunci
 * setelah itu). Tidak terkirim untuk Menunggu Validasi (mis. hasil Ragu) maupun ubah catatan.
 */
class AktivitasPemilahanObserver
{
    public function updated(AktivitasPemilahan $aktivitas): void
    {
        // Dinilai SEKARANG: update berikutnya (mis. poin_diperoleh) menimpa daftar perubahan model.
        if (! $aktivitas->wasChanged('status_verifikasi')
            || ! in_array($aktivitas->status_verifikasi, [AktivitasPemilahan::TERVERIFIKASI, AktivitasPemilahan::DITOLAK], true)) {
            return;
        }

        // Kirim setelah transaksi selesai agar poin & baris verifikasi sudah tersimpan.
        DB::afterCommit(function () use ($aktivitas) {
            try {
                $aktivitas->pengguna?->notify(new HasilVerifikasiNotification($aktivitas));
            } catch (Throwable $e) {
                // Gagal kirim email tidak boleh membatalkan hasil verifikasi yang sudah tersimpan.
                Log::warning('Notifikasi hasil verifikasi gagal dikirim', [
                    'id_aktivitas' => $aktivitas->id_aktivitas,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
