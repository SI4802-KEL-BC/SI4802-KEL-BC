<?php

namespace Tests\Feature\Pbi14\Concerns;

use App\Models\AktivitasPemilahan;
use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use Illuminate\Support\Collection;

/** Helper bersama test PBI-14. */
trait BuatAktivitas
{
    /** Buat N aktivitas milik $pengguna; $state bisa berupa array atau closure(int $i): array. */
    private function buatAktivitas(Pengguna $pengguna, int $jumlah = 1, array|callable $state = []): Collection
    {
        return collect(range(1, $jumlah))->map(function (int $i) use ($pengguna, $state) {
            $tambahan = is_callable($state) ? $state($i) : $state;

            return AktivitasPemilahan::factory()->create(array_merge([
                'id_pengguna' => $pengguna->id_pengguna,
                'id_lokasi' => LokasiTempatSampah::factory(),
                'id_kategori' => KategoriSampah::factory(),
            ], $tambahan));
        });
    }

    private function kodeAkt(AktivitasPemilahan $a): string
    {
        return 'AKT-'.str_pad((string) $a->id_aktivitas, 4, '0', STR_PAD_LEFT);
    }
}
