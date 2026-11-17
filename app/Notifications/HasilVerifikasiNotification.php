<?php

namespace App\Notifications;

use App\Models\AktivitasPemilahan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PBI-15: pemberitahuan ke pemilik saat aktivitas menjadi Terverifikasi atau Ditolak
 * (otomatis tahap 1 maupun Admin tahap 2). Dipicu dari AktivitasPemilahanObserver.
 */
class HasilVerifikasiNotification extends Notification
{
    public function __construct(public readonly AktivitasPemilahan $aktivitas) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->aktivitas;
        $kode = 'AKT-'.str_pad((string) $a->id_aktivitas, 4, '0', STR_PAD_LEFT);
        $diterima = $a->status_verifikasi === AktivitasPemilahan::TERVERIFIKASI;

        $mail = (new MailMessage)
            ->subject("Hasil verifikasi aktivitas {$kode}: {$a->status_verifikasi}")
            ->greeting('Halo, '.($notifiable->nama_lengkap ?? 'Pengguna').'!')
            ->line("Aktivitas pemilahan sampahmu **{$kode}** sudah diperiksa.")
            ->line("Status: **{$a->status_verifikasi}**");

        if ($diterima) {
            // Poin yang tampil = transaksi_poin.jumlah_poin milik aktivitas ini.
            $poin = (int) ($a->transaksiPoin()->value('jumlah_poin') ?? 0);
            $mail->line("Poin yang kamu dapat: **+{$poin}**");
        } else {
            $mail->line('Alasan: '.($a->alasan_penolakan ?: '-'))
                ->line('Aktivitas ini tidak mendapat poin.');
        }

        return $mail->action('Lihat detail', route('aktivitas.show', $a));
    }
}
