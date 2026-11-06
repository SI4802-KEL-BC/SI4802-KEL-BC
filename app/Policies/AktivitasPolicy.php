<?php

namespace App\Policies;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;

/** Hanya pemilik; ubah catatan & batalkan hanya saat Menunggu Validasi. */
class AktivitasPolicy
{
    public function view(Pengguna $pengguna, AktivitasPemilahan $aktivitas): bool
    {
        return $this->pemilik($pengguna, $aktivitas);
    }

    public function ubahCatatan(Pengguna $pengguna, AktivitasPemilahan $aktivitas): bool
    {
        return $this->pemilik($pengguna, $aktivitas) && $aktivitas->menungguValidasi();
    }

    public function batalkan(Pengguna $pengguna, AktivitasPemilahan $aktivitas): bool
    {
        return $this->pemilik($pengguna, $aktivitas) && $aktivitas->menungguValidasi();
    }

    private function pemilik(Pengguna $pengguna, AktivitasPemilahan $aktivitas): bool
    {
        // Cast agar tidak bergantung pada tipe (int/string) yang dikembalikan driver database.
        return (int) $aktivitas->id_pengguna === (int) $pengguna->id_pengguna;
    }
}
