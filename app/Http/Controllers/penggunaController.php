<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * PBI-01: Pengelolaan Profil Pengguna
 * PBI-02: Detail Profil Pengguna
 */
class PenggunaController extends Controller
{
    // Menampilkan detail profil
    public function show()
    {
        // Mengambil satu data pengguna untuk pengujian sementara
        $user = Pengguna::firstOrFail();

        return view('profil.show', compact('user'));
    }

    // Menampilkan form edit profil
    public function edit()
    {
        $user = Pengguna::firstOrFail();

        return view('profil.edit', compact('user'));
    }

    // Menyimpan perubahan profil
    public function update(Request $request)
    {
        $user = Pengguna::firstOrFail();

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'program_studi' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'foto_profil' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048'
            ],
        ]);

        // Upload foto profil jika ada
        if ($request->hasFile('foto_profil')) {

            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }

            $data['foto_profil'] = $request
                ->file('foto_profil')
                ->store('profil', 'public');
        }

        $user->update($data);

        return redirect()
            ->route('profil.show')
            ->with('status', 'Profil berhasil diperbarui!');
    }
}