<?php

namespace App\Services;

/**
 * Identifikasi gambar sampah (tahap 1). Implementasi nyata (model ML) belum ada;
 * pada development/testing dipakai MockVerifikasiOtomatisService atau di-mock di test.
 */
interface VerifikasiOtomatisService
{
    /**
     * @param  string  $fotoPath  path foto pada disk "public"
     * @return array{label: string, skor: float}
     */
    public function identifikasi(string $fotoPath): array;
}
