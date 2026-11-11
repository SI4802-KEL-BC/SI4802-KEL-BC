<?php

namespace App\Http\Requests;

use App\Models\AktivitasPemilahan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** PBI-14: validasi filter riwayat aktivitas (GET /aktivitas). */
class RiwayatAktivitasRequest extends FormRequest
{
    /** Filter salah: kembali ke riwayat tanpa filter (bukan ke halaman sebelumnya) dengan pesan error. */
    protected function getRedirectUrl(): string
    {
        return route('aktivitas.index');
    }

    public function authorize(): bool
    {
        return true; // akses dijaga middleware auth
    }

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'kategori' => ['nullable', 'integer', 'exists:kategori_sampah,id_kategori'],
            'status' => ['nullable', Rule::in([
                AktivitasPemilahan::MENUNGGU,
                AktivitasPemilahan::TERVERIFIKASI,
                AktivitasPemilahan::DITOLAK,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'dari.date' => 'Tanggal awal tidak valid.',
            'sampai.date' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal awal harus sebelum tanggal akhir',
            'kategori.exists' => 'Kategori tidak dikenal.',
            'kategori.integer' => 'Kategori tidak dikenal.',
            'status.in' => 'Status tidak dikenal.',
        ];
    }
}
