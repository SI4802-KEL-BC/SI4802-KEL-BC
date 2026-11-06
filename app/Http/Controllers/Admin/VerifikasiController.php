<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use App\Models\VerifikasiSampah;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Verifikasi manual tahap 2 (hanya Admin): aktivitas dengan hasil tahap 1 "Ragu". */
class VerifikasiController extends Controller
{
    public function __construct(private readonly PointService $poin) {}

    /** GET /admin/verifikasi — daftar antrean. */
    public function index(): View
    {
        $antrean = AktivitasPemilahan::antreanManual()
            ->with(['pengguna', 'kategori', 'lokasi', 'verifikasi'])
            ->orderBy('tanggal_waktu')
            ->get();

        return view('admin.verifikasi.index', ['antrean' => $antrean]);
    }

    /** GET /admin/verifikasi/{aktivitas} — foto, label, skor, aksi. */
    public function show(AktivitasPemilahan $aktivitas): View
    {
        $this->pastikanDalamAntrean($aktivitas);

        $aktivitas->load(['pengguna', 'kategori', 'lokasi', 'verifikasi']);

        return view('admin.verifikasi.show', [
            'aktivitas' => $aktivitas,
            'tahap1' => $aktivitas->verifikasi->firstWhere('tahap', VerifikasiSampah::TAHAP_OTOMATIS),
        ]);
    }

    /** POST /admin/verifikasi/{aktivitas} — setujui atau tolak (alasan wajib saat tolak). */
    public function proses(Request $request, AktivitasPemilahan $aktivitas): RedirectResponse
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'alasan' => ['nullable', 'required_if:aksi,tolak', 'string', 'max:255'],
        ], [
            'alasan.required_if' => 'Alasan wajib diisi',
            'aksi.in' => 'Aksi tidak dikenal',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($aktivitas, $data, $admin) {
            // Kunci baris & cek ulang agar tidak diproses ganda / setelah dibatalkan pengguna.
            $terkunci = AktivitasPemilahan::whereKey($aktivitas->getKey())->lockForUpdate()->firstOrFail();
            $this->pastikanDalamAntrean($terkunci);

            if ($data['aksi'] === 'setujui') {
                $this->setujui($terkunci, $admin, $data['alasan'] ?? null);
            } else {
                $this->tolak($terkunci, $admin, $data['alasan']);
            }
        });

        return redirect()->route('admin.verifikasi.index')->with(
            'status',
            $data['aksi'] === 'setujui' ? 'Aktivitas disetujui.' : 'Aktivitas ditolak.',
        );
    }

    private function setujui(AktivitasPemilahan $aktivitas, Pengguna $admin, ?string $catatan): void
    {
        // Tanpa kategori tidak ada aturan reward yang bisa dipakai: Admin harus menolak.
        if ($aktivitas->id_kategori === null) {
            throw ValidationException::withMessages([
                'aksi' => 'Kategori sampah belum teridentifikasi, aktivitas ini tidak dapat disetujui. Silakan tolak dengan alasan.',
            ]);
        }

        $aktivitas->update(['status_verifikasi' => AktivitasPemilahan::TERVERIFIKASI]);
        $this->poin->berikanPoin($aktivitas);
        VerifikasiSampah::verifikasiManual($aktivitas, $admin, VerifikasiSampah::VALID, $catatan);
    }

    private function tolak(AktivitasPemilahan $aktivitas, Pengguna $admin, string $alasan): void
    {
        $aktivitas->update([
            'status_verifikasi' => AktivitasPemilahan::DITOLAK,
            'alasan_penolakan' => $alasan,
            'poin_diperoleh' => 0, // Ditolak = poin 0, tanpa pengurangan poin
        ]);
        VerifikasiSampah::verifikasiManual($aktivitas, $admin, VerifikasiSampah::TIDAK_VALID, $alasan);
    }

    private function pastikanDalamAntrean(AktivitasPemilahan $aktivitas): void
    {
        abort_unless(
            AktivitasPemilahan::antreanManual()->whereKey($aktivitas->getKey())->exists(),
            404,
        );
    }
}
