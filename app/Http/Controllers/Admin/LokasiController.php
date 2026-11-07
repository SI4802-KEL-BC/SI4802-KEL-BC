<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LokasiTempatSampah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** PBI-10 - CRUD lokasi tempat sampah (admin). */
class LokasiController extends Controller
{
    // Perkiraan kotak kawasan Telkom University (Bandung). Sesuaikan jika perlu.
    private const LAT = [-6.990, -6.960];
    private const LNG = [107.615, 107.645];

    public function index()
    {
        return view('pages.table', [
            'title' => 'Kelola Lokasi Tempat Sampah',
            'subtitle' => 'Tambah titik baru, ubah informasi dan kategori yang diterima, atau nonaktifkan lokasi.',
            'columns' => ['Kode', 'Lokasi', 'Kategori', 'Status', 'Aksi'],
            'rows' => LokasiTempatSampah::with('kategori')->get()->map(fn ($l) => [
                $l->kode_titik, $l->nama_lokasi, $l->kategori->pluck('kode')->implode(', '), ucfirst($l->status),
                ['post' => route('admin.lokasi.toggle', $l), 'label' => $l->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'],
            ])->all(),
        ]);
    }

    public function store(Request $request)
    {
        [$data, $kategori] = $this->validated($request);
        LokasiTempatSampah::create($data + ['status' => 'aktif'])->kategori()->sync($kategori);

        return back()->with('status', 'Lokasi ditambahkan.');
    }

    public function update(Request $request, LokasiTempatSampah $lokasi)
    {
        [$data, $kategori] = $this->validated($request, $lokasi);
        $lokasi->update($data);
        $lokasi->kategori()->sync($kategori);

        return back()->with('status', 'Lokasi diperbarui.');
    }

    public function toggle(LokasiTempatSampah $lokasi)
    {
        $lokasi->update(['status' => $lokasi->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('status', 'Status lokasi diperbarui.');
    }

    private function validated(Request $request, ?LokasiTempatSampah $l = null): array
    {
        $v = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kode_titik' => ['required', 'string', 'max:10', Rule::unique('lokasi_tempat_sampah', 'kode_titik')->ignore($l?->id_lokasi, 'id_lokasi')],
            'latitude' => ['required', 'numeric', 'between:' . self::LAT[0] . ',' . self::LAT[1]],
            'longitude' => ['required', 'numeric', 'between:' . self::LNG[0] . ',' . self::LNG[1]],
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
