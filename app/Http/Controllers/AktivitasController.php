<?php

namespace App\Http\Controllers;

use App\Http\Requests\RiwayatAktivitasRequest;
use App\Models\AktivitasPemilahan;
use App\Models\KategoriSampah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AktivitasController extends Controller
{
    /** PBI-14 · GET /aktivitas — riwayat aktivitas milik pengguna login + filter. */
    public function index(RiwayatAktivitasRequest $request): View
    {
        $pengguna = $request->user();
        $filter = $request->validated();

        return view('aktivitas.index', [
            'aktivitas' => AktivitasPemilahan::lihatRiwayat($pengguna, $filter),
            'ringkasan' => AktivitasPemilahan::ringkasan($pengguna),
            'filter' => $filter,
            'adaFilter' => collect($filter)->filter(fn ($v) => filled($v))->isNotEmpty(),
            'daftarKategori' => KategoriSampah::orderBy('nama_kategori')->get(['id_kategori', 'nama_kategori']),
            'daftarStatus' => [AktivitasPemilahan::MENUNGGU, AktivitasPemilahan::TERVERIFIKASI, AktivitasPemilahan::DITOLAK],
        ]);
    }

    /** PBI-15 · GET /aktivitas/{aktivitas}/status — JSON {status, poin} untuk pembaruan otomatis (pemilik saja). */
    public function status(AktivitasPemilahan $aktivitas): JsonResponse
    {
        Gate::authorize('view', $aktivitas);

        $info = $aktivitas->lihatStatus();

        return response()
            ->json(['status' => $info['status'], 'poin' => $info['poin']])
            ->header('Cache-Control', 'no-store');
    }

    /** GET /aktivitas/{aktivitas} — detail aktivitas milik sendiri. */
    public function show(AktivitasPemilahan $aktivitas): View
    {
        Gate::authorize('view', $aktivitas);

        // View menghitung sendiri tahap 1/2 dan hak ubah (via AktivitasPolicy) dari relasi yang dimuat ini.
        return view('aktivitas.show', ['aktivitas' => $aktivitas->lihatDetail()]);
    }

    /** PATCH /aktivitas/{aktivitas}/catatan — hanya saat Menunggu Validasi. */
    public function ubahCatatan(Request $request, AktivitasPemilahan $aktivitas): RedirectResponse
    {
        Gate::authorize('ubahCatatan', $aktivitas);

        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $aktivitas->ubahCatatan($data['catatan'] ?? null);

        return redirect()->route('aktivitas.show', $aktivitas)->with('status', 'Catatan berhasil disimpan.');
    }

    /** DELETE /aktivitas/{aktivitas} — batalkan = hapus (hanya saat Menunggu Validasi). */
    public function destroy(AktivitasPemilahan $aktivitas): RedirectResponse
    {
        Gate::authorize('batalkan', $aktivitas);

        $aktivitas->batalkan();

        return redirect()->route('pindai.index')->with('status', 'Aktivitas berhasil dibatalkan.');
    }
}
