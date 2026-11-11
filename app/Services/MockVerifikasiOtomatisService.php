<?php

namespace App\Services;

/** Simulasi model klasifikasi: hasil tetap dari config('pilahku.mock'). */
class MockVerifikasiOtomatisService implements VerifikasiOtomatisService
{
    public function identifikasi(string $fotoPath): array
    {
        return [
            'label' => (string) config('pilahku.mock.label'),
            'skor' => (float) config('pilahku.mock.skor'),
        ];
    }
}
