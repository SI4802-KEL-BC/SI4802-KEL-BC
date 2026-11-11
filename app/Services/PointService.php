<?php

namespace App\Services;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\TransaksiPoin;

/**
 * STUB PBI-13: aturan & saldo poin sebenarnya milik PBI-16/17/18.
 * Hanya dipanggil saat aktivitas menjadi Terverifikasi. batas_per_hari diabaikan.
 */
class PointService
{
    /** Hitung & catat poin; mengembalikan jumlah poin yang diberikan (0 bila tak ada aturan aktif). */
    public function berikanPoin(AktivitasPemilahan $aktivitas): int
    {
        $aturan = AturanReward::where('id_kategori', $aktivitas->id_kategori)
            ->where('status', 'aktif')
            ->first();

        $poin = $aturan ? (int) round($aturan->poin_per_satuan * (float) $aktivitas->jumlah) : 0;

        if ($poin > 0) {
            TransaksiPoin::create([
                'id_pengguna' => $aktivitas->id_pengguna,
                'id_aktivitas' => $aktivitas->id_aktivitas,
                'tanggal' => now(),
                'jenis' => 'Perolehan',
                'jumlah_poin' => $poin,
                'keterangan' => 'Poin aktivitas pemilahan #'.$aktivitas->id_aktivitas,
            ]);
        }

        $aktivitas->update(['poin_diperoleh' => $poin]);

        return $poin;
    }
}
