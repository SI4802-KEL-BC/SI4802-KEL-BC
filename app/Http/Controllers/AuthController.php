<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** PBI-01: registrasi, masuk, dan keluar akun memakai tabel pengguna (email_kampus + kata_sandi). */
class AuthController extends Controller
{
    public function form(): View
    {
        return view('auth.login');
    }

    public function formDaftar(): View
    {
        return view('auth.register');
    }

    /** TC-P01-01 / TC-P01-04: registrasi akun baru berperan User. */
    public function daftar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_panggilan' => ['nullable', 'string', 'max:50'],
            'nim_nip' => ['required', 'regex:/^[0-9]{5,20}$/', Rule::unique('pengguna', 'nim_nip')],
            'email_kampus' => ['required', 'email', 'max:100', Rule::unique('pengguna', 'email_kampus')],
            'program_studi' => ['nullable', 'string', 'max:100'],
            // Maks. 15 karakter, sama dengan kolom pengguna.no_hp.
            'no_hp' => ['nullable', 'regex:/^(\+62[0-9]{8,12}|0[0-9]{8,14})$/'],
            'kata_sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nim_nip.required' => 'NIM/NIP wajib diisi.',
            'nim_nip.regex' => 'NIM/NIP hanya berisi angka (5–20 digit).',
            'nim_nip.unique' => 'NIM/NIP sudah terdaftar.',
            'email_kampus.required' => 'Email kampus wajib diisi.',
            'email_kampus.email' => 'Format email kampus tidak valid.',
            'email_kampus.unique' => 'Email kampus sudah terdaftar.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
            'kata_sandi.min' => 'Kata sandi minimal 8 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        Pengguna::create($data + [
            'peran' => 'User',
            'status_akun' => 'aktif',
            'tanggal_bergabung' => now()->toDateString(),
        ]);

        return redirect()->route('login')->with('status', 'Akun berhasil dibuat. Silakan masuk.');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
            'kata_sandi' => ['required', 'string'],
        ], [
            'email.required' => 'Email kampus wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
        ]);

        $cocok = Auth::attempt(['email_kampus' => $data['email'], 'password' => $data['kata_sandi']]);

        if (! $cocok) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

        /** @var Pengguna $pengguna */
        $pengguna = Auth::user();

        if (strtolower((string) $pengguna->status_akun) !== 'aktif') {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Akun ini tidak aktif. Hubungi admin.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($pengguna->isAdmin() ? '/admin/verifikasi' : '/pindai');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Kamu sudah keluar.');
    }
}
