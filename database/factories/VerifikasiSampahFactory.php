<?php

namespace Database\Factories;

use App\Models\AktivitasPemilahan;
use App\Models\VerifikasiSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VerifikasiSampah> */
class VerifikasiSampahFactory extends Factory
{
    protected $model = VerifikasiSampah::class;

    public function definition(): array
    {
        return [
            'id_aktivitas' => AktivitasPemilahan::factory(),
            'id_verifikator' => null,
            'tahap' => VerifikasiSampah::TAHAP_OTOMATIS,
            'hasil' => VerifikasiSampah::VALID,
            'skor_keyakinan' => 0.92,
            'label_terdeteksi' => 'plastic',
            'foto_sampah' => 'bukti/contoh.jpg',
            'waktu_verifikasi' => now(),
            'keterangan' => null,
        ];
    }

    public function ragu(float $skor = 0.55): static
    {
        return $this->state(['hasil' => VerifikasiSampah::RAGU, 'skor_keyakinan' => $skor]);
    }
}
