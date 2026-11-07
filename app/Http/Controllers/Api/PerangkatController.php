<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FotoRequest;
use App\Models\AktivitasPemilahan;
use App\Models\LokasiTempatSampah;
use App\Models\SesiPembuangan;
use App\Services\VerifikasiTahap1;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * API untuk kamera/perangkat di tong sampah. Pengguna memindai QR tong (halaman /pindai) sehingga sesi
 * Aktif sudah ada atas namanya; perangkat hanya mengirim foto untuk sesi aktif di tongnya sendiri.
 */
class PerangkatController extends Controller
{
    public function __construct(private readonly VerifikasiTahap1 $tahap1) {}

    /** POST /api/perangkat/foto — simpan aktivitas Menunggu Validasi, sesi Selesai, lalu verifikasi tahap 1. */
    public function foto(FotoRequest $request): JsonResponse
    {
        $lokasi = LokasiTempatSampah::where('kode_perangkat', $request->validated('kode_perangkat'))
            ->where('status', 'aktif')
            ->first();

        if (! $lokasi) {
            return $this->tolak('Perangkat tidak terdaftar');
        }

        $sesi = SesiPembuangan::where('id_lokasi', $lokasi->id_lokasi)
            ->where('status_sesi', SesiPembuangan::AKTIF)
            ->where('kedaluwarsa_pada', '>', now())
            ->latest('waktu_scan')
            ->first();

        if (! $sesi) {
            // Sesi yang menunggu foto tetapi sudah lewat batas waktu ditandai Kedaluwarsa (tanpa aktivitas).
            $lewat = SesiPembuangan::where('id_lokasi', $lokasi->id_lokasi)
                ->where('status_sesi', SesiPembuangan::AKTIF)
                ->where('kedaluwarsa_pada', '<=', now())
                ->get();
            $lewat->each->kedaluwarsakan();

            return $this->tolak($lewat->isNotEmpty()
                ? 'Sesi pembuangan telah kedaluwarsa'
                : 'Tidak ada sesi aktif di tong ini. Pindai QR tong terlebih dahulu');
        }

        $hasil = DB::transaction(function () use ($request, $sesi) {
            // Cek ulang dengan kunci baris agar dua foto bersamaan tidak membuat dua aktivitas untuk satu sesi.
            $terkunci = SesiPembuangan::whereKey($sesi->id_sesi)->lockForUpdate()->first();
            if (! $terkunci || $terkunci->status_sesi !== SesiPembuangan::AKTIF) {
                return null;
            }

            $path = $request->file('foto')->store('bukti', 'public');

            $aktivitas = AktivitasPemilahan::simpan([
                'id_pengguna' => $terkunci->id_pengguna,
                'id_lokasi' => $terkunci->id_lokasi,
                'id_sesi' => $terkunci->id_sesi,
                'foto_bukti' => $path,
            ]);

            $terkunci->selesaikan();

            // Verifikasi otomatis tahap 1 (Valid / Tidak Valid / Ragu).
            $verifikasi = $this->tahap1->proses($aktivitas);

            return [$aktivitas->refresh(), $verifikasi];
        });

        if (! $hasil) {
            return $this->tolak('Sesi sudah tidak aktif');
        }

        [$aktivitas, $verifikasi] = $hasil;

        return response()->json([
            'message' => 'Foto diterima, verifikasi tahap 1 selesai',
            'data' => [
                'id_aktivitas' => $aktivitas->id_aktivitas,
                'status_verifikasi' => $aktivitas->status_verifikasi,
                'hasil_tahap_1' => $verifikasi->hasil,
            ],
        ], 201);
    }

    private function tolak(string $pesan): JsonResponse
    {
        return response()->json(['message' => $pesan], 422);
    }
}
