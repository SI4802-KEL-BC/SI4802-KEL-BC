<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FotoRequest;
use App\Http\Requests\ScanRequest;
use App\Models\AktivitasPemilahan;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Services\QrTokenService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PerangkatController extends Controller
{
    private const PESAN_QR_TIDAK_VALID = 'QR tidak valid atau kedaluwarsa';

    public function __construct(private readonly QrTokenService $qr) {}

    /** POST /api/perangkat/scan — validasi QR, buat sesi_pembuangan Aktif. */
    public function scan(ScanRequest $request): JsonResponse
    {
        $token = $request->validated('token_qr');
        $data = $this->qr->baca($token);

        $pengguna = $data ? Pengguna::find($data['id_pengguna']) : null;

        if (! $pengguna || $pengguna->status_akun !== 'aktif') {
            return $this->tolak(self::PESAN_QR_TIDAK_VALID);
        }

        $hash = $this->qr->hash($token);

        if (SesiPembuangan::where('token_qr', $hash)->exists()) {
            return $this->tolak(self::PESAN_QR_TIDAK_VALID); // sudah dipakai
        }

        $lokasi = LokasiTempatSampah::where('kode_perangkat', $request->validated('kode_perangkat'))
            ->where('status', 'aktif')
            ->first();

        if (! $lokasi) {
            return $this->tolak('Perangkat tidak terdaftar');
        }

        try {
            $sesi = SesiPembuangan::buatSesi($pengguna->id_pengguna, $lokasi->id_lokasi, $hash);
        } catch (UniqueConstraintViolationException) {
            return $this->tolak(self::PESAN_QR_TIDAK_VALID); // balapan pemakaian ganda
        }

        return response()->json([
            'message' => 'QR valid, sesi pembuangan dimulai',
            'data' => [
                'id_sesi' => $sesi->id_sesi,
                'status_sesi' => $sesi->status_sesi,
                'kedaluwarsa_pada' => $sesi->kedaluwarsa_pada->toIso8601String(),
            ],
        ]);
    }

    /** POST /api/perangkat/foto — simpan aktivitas Menunggu Validasi, sesi Selesai. */
    public function foto(FotoRequest $request): JsonResponse
    {
        $sesi = SesiPembuangan::where('token_qr', $this->qr->hash($request->validated('token_qr')))->first();

        if (! $sesi) {
            return $this->tolak(self::PESAN_QR_TIDAK_VALID);
        }

        if ($sesi->status_sesi !== SesiPembuangan::AKTIF) {
            return $this->tolak('Sesi sudah tidak aktif');
        }

        if ($sesi->sudahLewatBatas()) {
            $sesi->kedaluwarsakan();

            return $this->tolak('Sesi pembuangan telah kedaluwarsa');
        }

        $aktivitas = DB::transaction(function () use ($request, $sesi) {
            $path = $request->file('foto')->store('bukti', 'public');

            $aktivitas = AktivitasPemilahan::simpan([
                'id_pengguna' => $sesi->id_pengguna,
                'id_lokasi' => $sesi->id_lokasi,
                'id_sesi' => $sesi->id_sesi,
                'foto_bukti' => $path,
            ]);

            $sesi->selesaikan();

            return $aktivitas;
        });

        return response()->json([
            'message' => 'Foto diterima, aktivitas menunggu validasi',
            'data' => [
                'id_aktivitas' => $aktivitas->id_aktivitas,
                'status_verifikasi' => $aktivitas->status_verifikasi,
            ],
        ], 201);
    }

    private function tolak(string $pesan): JsonResponse
    {
        return response()->json(['message' => $pesan], 422);
    }
}
