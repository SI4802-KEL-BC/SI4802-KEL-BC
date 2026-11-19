<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

/**
 * PBI-01: Pengelolaan Profil Pengguna
 * PBI-02: Detail Profil Pengguna
 */
class PenggunaController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profil.show', ['user' => $user]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profil.edit', ['user' => $user]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // TC-P01-05: data dengan format tidak sesuai ditolak.
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_panggilan' => ['nullable', 'string', 'max:50'],
            'program_studi' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'regex:/^(\+62|0)[0-9]{8,13}$/'],
            'foto_profil' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_panggilan.max' => 'Nama panggilan maksimal 50 karakter.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'foto_profil.image' => 'Foto profil harus berupa gambar JPG atau PNG.',
            'foto_profil.mimes' => 'Foto profil harus berupa gambar JPG atau PNG.',
            'foto_profil.max' => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        if ($request->hasFile('foto_profil')) {
            $fotoProfil = $request->file('foto_profil')->store('profil', 'public');

            if ($fotoProfil === false) {
                throw new RuntimeException('Foto profil gagal disimpan.');
            }

            $fotoLama = $user->foto_profil;
            $data['foto_profil'] = $fotoProfil;
        } else {
            $fotoLama = null;
        }

        $user->update($data);

        if ($fotoLama !== null) {
            Storage::disk('public')->delete($fotoLama);
        }

        return redirect()->route('profil.show')->with('status', 'Profil berhasil diperbarui!');
    }

    public function ubahKataSandi(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => ['required', 'current_password'],
            'kata_sandi' => ['required', 'string', 'min:8', 'different:password_lama'],
        ], [
            'password_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'password_lama.current_password' => 'Kata sandi saat ini salah.',
            'kata_sandi.required' => 'Kata sandi baru wajib diisi.',
            'kata_sandi.min' => 'Kata sandi baru minimal 8 karakter.',
            'kata_sandi.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        $request->user()->update(['kata_sandi' => $request->input('kata_sandi')]);

        return redirect()->route('profil.edit')->with('status', 'Kata sandi berhasil diperbarui.');
    }

    /**
     * TC-P01-03 / TC-P01-06: menonaktifkan akun setelah konfirmasi ("NONAKTIFKAN") dan kata sandi.
     * Data tidak dihapus agar riwayat aktivitas dan poin tetap utuh; akun nonaktif tidak bisa login.
     */
    public function nonaktifkan(Request $request): RedirectResponse
    {
        $request->validate([
            'konfirmasi' => ['required', 'in:NONAKTIFKAN'],
            'password' => ['required', 'current_password'],
        ], [
            'konfirmasi.required' => 'Ketik NONAKTIFKAN untuk mengonfirmasi.',
            'konfirmasi.in' => 'Ketik NONAKTIFKAN untuk mengonfirmasi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.current_password' => 'Kata sandi salah.',
        ]);

        $request->user()->update(['status_akun' => 'nonaktif']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Akun berhasil dinonaktifkan.');
    }
}
