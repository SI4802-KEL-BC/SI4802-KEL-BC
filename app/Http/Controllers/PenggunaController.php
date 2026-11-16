<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'program_studi' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'foto_profil' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
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
}
