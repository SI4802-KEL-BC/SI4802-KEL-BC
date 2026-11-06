<?php

namespace App\Http\Controllers;

use App\Models\SesiPembuangan;
use App\Services\QrTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrIdController extends Controller
{
    private const SESSION_TOKEN = 'qr_id.token';

    private const SESSION_BERLAKU = 'qr_id.berlaku_hingga';

    public function __construct(private readonly QrTokenService $qr) {}

    /** GET /qr-id — QR sementara milik pengguna + hitung mundur. */
    public function index(Request $request): View
    {
        $token = $request->session()->get(self::SESSION_TOKEN);
        $berlakuHingga = (int) $request->session()->get(self::SESSION_BERLAKU, 0);

        // Pakai ulang QR di sesi browser hanya bila masih berlaku dan belum dipindai.
        if (! $token || $berlakuHingga <= now()->getTimestamp() || $this->sudahDipakai($token)) {
            [$token, $berlakuHingga] = $this->terbitkan($request);
        }

        return view('qr-id', [
            'qrSvg' => $this->qr->svg($token),
            'sisaDetik' => max(0, $berlakuHingga - now()->getTimestamp()),
            'qrTtl' => config('pilahku.qr_ttl'),
        ]);
    }

    /** POST /qr-id/regenerate — buat QR baru. */
    public function regenerate(Request $request): RedirectResponse
    {
        $this->terbitkan($request);

        return redirect()->route('qr-id.index')->with('status', 'QR ID baru berhasil dibuat.');
    }

    /** @return array{0: string, 1: int} */
    private function terbitkan(Request $request): array
    {
        $baru = $this->qr->buat($request->user());
        $berlakuHingga = $baru['berlaku_hingga']->getTimestamp();

        $request->session()->put([
            self::SESSION_TOKEN => $baru['token'],
            self::SESSION_BERLAKU => $berlakuHingga,
        ]);

        return [$baru['token'], $berlakuHingga];
    }

    private function sudahDipakai(string $token): bool
    {
        return SesiPembuangan::where('token_qr', $this->qr->hash($token))->exists();
    }
}
