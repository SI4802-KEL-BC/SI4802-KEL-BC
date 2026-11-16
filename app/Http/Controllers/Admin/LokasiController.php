<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LokasiController extends Controller
{
    private const LAT = [-6.990, -6.960];

    private const LNG = [107.615, 107.645];

    public function index()
    {
        return view('admin.lokasi.index', [
            'lokasis' => LokasiTempatSampah::with('kategori')->orderBy('nama_lokasi')->get(),
            'kategoris' => KategoriSampah::orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request)
    {
        [$data, $kategori] = $this->validated($request);
        $lokasi = LokasiTempatSampah::create($data + [
            'kode_perangkat' => Str::random(30),
            'status' => 'aktif',
        ]);
        $lokasi->kategori()->sync($kategori);

        return back()->with('status', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, LokasiTempatSampah $lokasi)
    {
        [$data, $kategori] = $this->validated($request, $lokasi);
        $lokasi->update($data);
        $lokasi->kategori()->sync($kategori);

        return back()->with('status', 'Lokasi berhasil diperbarui.');
    }

    public function toggle(LokasiTempatSampah $lokasi)
    {
        $lokasi->update(['status' => $lokasi->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('status', 'Status lokasi berhasil diperbarui.');
    }

    private function validated(Request $request, ?LokasiTempatSampah $lokasi = null): array
    {
        $v = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kode_titik' => ['required', 'string', 'max:10', Rule::unique('lokasi_tempat_sampah', 'kode_titik')->ignore($lokasi?->id_lokasi, 'id_lokasi')],
            'area' => ['required', 'string', 'max:50'],
            'latitude' => ['required', 'numeric', 'between:'.self::LAT[0].','.self::LAT[1]],
            'longitude' => ['required', 'numeric', 'between:'.self::LNG[0].','.self::LNG[1]],
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => ['exists:kategori_sampah,id_kategori'],
        ], [
            'latitude.between' => 'Koordinat berada di luar kawasan Telkom University.',
            'longitude.between' => 'Koordinat berada di luar kawasan Telkom University.',
        ]);

        $kategori = $v['kategori'];
        unset($v['kategori']);

        return [$v, $kategori];
    }
}
