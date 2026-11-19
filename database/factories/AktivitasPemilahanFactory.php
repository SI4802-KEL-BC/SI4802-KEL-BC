<?php

namespace Database\Factories;

use App\Models\AktivitasPemilahan;
use App\Models\KategoriSampah;
use App\Models\SesiPembuangan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AktivitasPemilahan> */
class AktivitasPemilahanFactory extends Factory
{
    protected $model = AktivitasPemilahan::class;

    public function definition(): array
    {
        // Pengguna & lokasi mengikuti sesi agar konsisten.
        return [
            'id_sesi' => SesiPembuangan::factory(),
            'id_pengguna' => fn (array $attr) => SesiPembuangan::find($attr['id_sesi'])->id_pengguna,
            'id_lokasi' => fn (array $attr) => SesiPembuangan::find($attr['id_sesi'])->id_lokasi,
            'id_kategori' => KategoriSampah::factory(),
            'tanggal_waktu' => now(),
            'jumlah' => 1,
            'foto_bukti' => 'bukti/contoh.jpg',
            'catatan' => null,
            'status_verifikasi' => AktivitasPemilahan::MENUNGGU,
            'alasan_penolakan' => null,
            'poin_diperoleh' => 0,
        ];
    }

    public function terverifikasi(int $poin = 10): static
    {
        return $this->state(['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI, 'poin_diperoleh' => $poin]);
    }

    public function ditolak(string $alasan = 'Kategori tidak sesuai tempat sampah'): static
    {
        return $this->state(['status_verifikasi' => AktivitasPemilahan::DITOLAK, 'alasan_penolakan' => $alasan]);
    }
}
