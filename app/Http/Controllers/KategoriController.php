<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KategoriSampah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = KategoriSampah::orderBy('nama_kategori')->get();

        return view('kategori', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['status'] = 'aktif';
        
        KategoriSampah::create($data);

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriSampah $kategori)
    {
        $data = $this->rules($request, $kategori);
        // Status dikelola lewat aksi aktif/nonaktif agar edit data tidak mengubahnya.
        $kategori->update($data);

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function toggle(KategoriSampah $kategori)
    {
        $kategori->update(['status' => $kategori->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('status', 'Status kategori berhasil diperbarui.');
    }

    private function rules(Request $request, ?KategoriSampah $k = null): array
    {
        return $request->validate([
            'nama_kategori' => ['required', 'string', 'max:50'],
            'kode' => ['required', 'string', 'max:10', Rule::unique('kategori_sampah', 'kode')->ignore($k?->id_kategori, 'id_kategori')],
            'warna_label' => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string'],
            'karakteristik' => ['nullable', 'string'],
            'cara_penanganan' => ['nullable', 'string'],
        ]);
    }
}
