<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Masuk dan keluar akun memakai tabel pengguna (email_kampus + kata_sandi). */
class AuthController extends Controller
{
    public function form(): View
    {
        return view('auth.login');
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
