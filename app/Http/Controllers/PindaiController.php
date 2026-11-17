<?php

namespace App\Http\Controllers;

use App\Models\AktivitasPemilahan;
use App\Models\LokasiTempatSampah;
use App\Models\SesiPembuangan;
use App\Services\PindaiTongService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pengguna memindai QR yang tertempel di tong sampah (kamera HP/laptop atau memasukkan kode tong). */
class PindaiController extends Controller
{
    public function __construct(private readonly PindaiTongService $pindai) {}

    /** GET /pindai — status sesi, pemindai kamera, input kode tong, aktivitas terakhir. */
    public function index(Request $request): View
    {
        $idPengguna = $request->user()->id_pengguna;

        return view('pindai', [
            'sessionTtl' => (int) config('pilahku.session_ttl'),
            // Sesi yang sedang menunggu foto dari kamera tong, bila ada.
            'sesiAktif' => SesiPembuangan::with('lokasi')
                ->where('id_pengguna', $idPengguna)
                ->where('status_sesi', SesiPembuangan::AKTIF)
                ->where('kedaluwarsa_pada', '>', now())
                ->latest('waktu_scan')
                ->first(),
            // Tiga aktivitas terakhir milik pengguna.
            'aktivitasTerbaru' => AktivitasPemilahan::with(['kategori', 'lokasi'])
                ->where('id_pengguna', $idPengguna)
                ->orderByDesc('tanggal_waktu')
                ->orderByDesc('id_aktivitas')
                ->limit(3)
                ->get(),
        ]);
    }

    /** GET /pindai/{kode} — tujuan URL di QR tong (dibuka kamera bawaan HP atau pemindai di halaman ini). */
    public function mulai(Request $request, string $kode): RedirectResponse
    {
        return $this->proses($request, $kode);
    }

    /** POST /pindai — memasukkan kode tong secara manual (cadangan bila kamera tidak bisa dipakai). */
    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['kode_perangkat' => ['required', 'string', 'max:30']],
            ['kode_perangkat.required' => 'Masukkan kode tong sampah.'],
        );

        return $this->proses($request, $data['kode_perangkat']);
    }

    private function proses(Request $request, string $kode): RedirectResponse
    {
        $kode = trim($kode);
        $lokasi = LokasiTempatSampah::where('kode_perangkat', $kode)->first();

        if (! $lokasi) {
            return redirect()->route('pindai.index')
                ->withInput(['kode_perangkat' => $kode])
                ->withErrors(['kode_perangkat' => "Kode tong \"{$kode}\" tidak dikenali. Pastikan QR atau kodenya benar."]);
        }

        $hasil = $this->pindai->mulai($request->user(), $lokasi);

        if (! $hasil['ok']) {
            return redirect()->route('pindai.index')->withErrors(['kode_perangkat' => $hasil['pesan']]);
        }

        return redirect()->route('pindai.index')->with('status', $hasil['pesan']);
    }
}
