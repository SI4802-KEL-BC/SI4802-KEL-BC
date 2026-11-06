<?php

namespace App\Services;

use App\Models\Pengguna;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * QR ID = token terenkripsi (id_pengguna + waktu berlaku). Sekali pakai dijamin
 * oleh sesi_pembuangan.token_qr (hash sha256, unique).
 */
class QrTokenService
{
    /** @return array{token: string, berlaku_hingga: CarbonInterface} */
    public function buat(Pengguna $pengguna): array
    {
        $berlakuHingga = now()->addSeconds(config('pilahku.qr_ttl'));

        $token = Crypt::encryptString(json_encode([
            'uid' => $pengguna->id_pengguna,
            'exp' => $berlakuHingga->getTimestamp(),
            'nonce' => Str::random(16),
        ]));

        return ['token' => $token, 'berlaku_hingga' => $berlakuHingga];
    }

    /**
     * Dekripsi & periksa masa berlaku.
     *
     * @return array{id_pengguna: int, berlaku_hingga: int}|null null bila acak/rusak/kedaluwarsa
     */
    public function baca(string $token): ?array
    {
        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($data) || ! isset($data['uid'], $data['exp']) || $data['exp'] < now()->getTimestamp()) {
            return null;
        }

        return ['id_pengguna' => (int) $data['uid'], 'berlaku_hingga' => (int) $data['exp']];
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
