<?php

namespace App\Http\Controllers\Titik;

use App\Http\Controllers\Controller;
use App\Models\KategoriTitik;
use App\Models\TitikLokasi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** PBI-10 - CRUD lokasi tempat sampah (admin). Mandiri, belum memakai login sampai PBI-01 masuk. */
class AdminTitikController extends Controller
{
    // Perkiraan kotak kawasan Telkom University (Bandung).
    private const LAT = [-6.990, -6.960];
    private const LNG = [107.615, 107.645];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $area = $request->query('area');
        $status = $request->query('status');

        $daftar = TitikLokasi::with('kategori')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($w) => $w->where('nama_lokasi', 'like', "%{$q}%")->orWhere('kode_titik', 'like', "%{$q}%")
            ))
            ->when($area, fn ($query) => $query->where('area', $area))
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('kode_titik')
            ->get();

        // Panel form di kanan: ?tambah=1 untuk lokasi baru, ?edit={id} untuk mengubah.
        $titik = null;
        if ($request->boolean('tambah')) {
            $titik = new TitikLokasi();
        } elseif ($request->filled('edit')) {
            $titik = TitikLokasi::with('kategori')->findOrFail($request->query('edit'));
        }

        return view('titik.admin.index', [
            'daftar' => $daftar,
            'areas' => TitikLokasi::orderBy('area')->distinct()->pluck('area'),
            'kategoris' => KategoriTitik::orderBy('nama_kategori')->get(),
            'titik' => $titik,
            'titikPeta' => TitikLokasi::all()->map(fn ($t) => [
                'id' => $t->id,
                'nama' => $t->nama_lokasi,
                'lat' => $t->latitude,
                'lng' => $t->longitude,
                'aktif' => $t->status === 'aktif',
            ])->values(),

        ]);
    }
        public function store(Request $request)
    {
        [$data, $kategori] = $this->validated($request);

        $titik = TitikLokasi::create($data);
        $titik->kategori()->sync($kategori);

        return redirect()->route('titik.admin.index')->with('status', 'Lokasi berhasil ditambahkan.');
    }
        public function update(Request $request, TitikLokasi $titik)
    {
        [$data, $kategori] = $this->validated($request, $titik);

        $titik->update($data);
        $titik->kategori()->sync($kategori);

        return redirect()->route('titik.admin.index')->with('status', 'Lokasi berhasil diperbarui.');
    }
       public function toggle(TitikLokasi $titik)
    {
        $titik->update(['status' => $titik->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('status', $titik->status === 'aktif' ? 'Lokasi diaktifkan.' : 'Lokasi dinonaktifkan.');
    }

        private function validated(Request $request, ?TitikLokasi $titik = null): array
    {
        $kawasan = fn (array $batas) => function ($attribute, $value, $fail) use ($batas) {
            if ((float) $value < $batas[0] || (float) $value > $batas[1]) {
                $fail('Koordinat berada di luar kawasan Telkom University.');
            }
        };

        $v = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kode_titik' => ['required', 'string', 'max:10',
                Rule::unique('titik_lokasi', 'kode_titik')->ignore($titik?->id)],
            'area' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'fasilitas' => ['nullable', 'string', 'max:255'],
            'kode_perangkat' => ['nullable', 'string', 'max:30',
                Rule::unique('titik_lokasi', 'kode_perangkat')->ignore($titik?->id)],
            'latitude' => ['bail', 'required', 'numeric', 'between:-90,90', $kawasan(self::LAT)],
            'longitude' => ['bail', 'required', 'numeric', 'between:-180,180', $kawasan(self::LNG)],
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => ['exists:kategori_titik,id'],
        ], [
            'kode_titik.unique' => 'Kode titik sudah digunakan.',
            'kode_perangkat.unique' => 'Kode perangkat sudah digunakan.',
            'latitude.numeric' => 'Format koordinat tidak valid.',
            'latitude.between' => 'Format koordinat tidak valid.',
            'longitude.numeric' => 'Format koordinat tidak valid.',
            'longitude.between' => 'Format koordinat tidak valid.',
            'kategori.required' => 'Pilih minimal satu kategori yang diterima.',
            'kategori.min' => 'Pilih minimal satu kategori yang diterima.',
        ]);

        $kategori = $v['kategori'];
        unset($v['kategori']);
        $v['status'] = $request->boolean('aktif') ? 'aktif' : 'nonaktif';

        return [$v, $kategori];
    }
}
