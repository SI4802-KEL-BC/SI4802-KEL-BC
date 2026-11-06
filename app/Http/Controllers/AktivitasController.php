<?php

namespace App\Http\Controllers;

use App\Models\AktivitasPemilahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AktivitasController extends Controller
{
    /** GET /aktivitas/{aktivitas} — detail aktivitas milik sendiri. */
    public function show(AktivitasPemilahan $aktivitas): View
    {
        Gate::authorize('view', $aktivitas);

        $aktivitas->lihatDetail();

        return view('aktivitas.show', [
            'aktivitas' => $aktivitas,
            'tahap1' => $aktivitas->verifikasi->firstWhere('tahap', 1),
            'tahap2' => $aktivitas->verifikasi->firstWhere('tahap', 2),
            'bisaDiubah' => Gate::allows('ubahCatatan', $aktivitas),
        ]);
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

        return redirect()->route('qr-id.index')->with('status', 'Aktivitas berhasil dibatalkan.');
    }
}
