<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LokasiController extends Controller
{
    private const LAT = [-6.990, -6.960];

    private const LNG = [107.615, 107.645];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $area = $request->query('area');
        $status = $request->query('status');

        $lokasis = LokasiTempatSampah::with('kategori')
            ->when($q !== '', fn (Builder $query) => $query->where(
                fn (Builder $search) => $search->where('nama_lokasi', 'like', "%{$q}%")
                    ->orWhere('kode_titik', 'like', "%{$q}%")
            ))
            ->when($area, fn (Builder $query) => $query->where('area', $area))
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn (Builder $query) => $query->where('status', $status))
            ->orderBy('kode_titik')
            ->get();

        $titik = null;
        if ($request->boolean('tambah')) {
            $titik = new LokasiTempatSampah;
        } elseif ($request->filled('edit')) {
            $titik = LokasiTempatSampah::with('kategori')->findOrFail($request->query('edit'));
        }

        return view('titik.admin.index', [
            'daftar' => $lokasis,
            'lokasis' => $lokasis,
            'areas' => LokasiTempatSampah::query()->orderBy('area')->distinct()->pluck('area'),
            'kategoris' => KategoriSampah::orderBy('nama_kategori')->get(),
            'titik' => $titik,
            'titikPeta' => LokasiTempatSampah::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(fn (LokasiTempatSampah $lokasi) => [
                    'id' => $lokasi->id_lokasi,
                    'kode' => $lokasi->kode_titik,
                    'nama' => $lokasi->nama_lokasi,
                    'lat' => $lokasi->latitude,
                    'lng' => $lokasi->longitude,
                    'aktif' => $lokasi->status === 'aktif',
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $kategori] = $this->validated($request);
        if (blank($data['kode_perangkat'] ?? null)) {
            $data['kode_perangkat'] = Str::random(30);
        }

        $lokasi = LokasiTempatSampah::create($data);
        $lokasi->kategori()->sync($kategori);

        return redirect()->route('admin.lokasi.index')->with('status', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, LokasiTempatSampah $lokasi): RedirectResponse
    {
        [$data, $kategori] = $this->validated($request, $lokasi);
        if (blank($data['kode_perangkat'] ?? null)) {
            unset($data['kode_perangkat']);
        }

        $lokasi->update($data);
        $lokasi->kategori()->sync($kategori);

        return redirect()->route('admin.lokasi.index')->with('status', 'Lokasi berhasil diperbarui.');
    }

    public function toggle(LokasiTempatSampah $lokasi): RedirectResponse
    {
        $lokasi->update(['status' => $lokasi->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('status', 'Status lokasi berhasil diperbarui.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function validated(Request $request, ?LokasiTempatSampah $lokasi = null): array
    {
        $v = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kode_titik' => ['required', 'string', 'max:10', Rule::unique('lokasi_tempat_sampah', 'kode_titik')->ignore($lokasi?->id_lokasi, 'id_lokasi')],
            'area' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'fasilitas' => ['nullable', 'string', 'max:255'],
            'kode_perangkat' => ['nullable', 'string', 'max:30', Rule::unique('lokasi_tempat_sampah', 'kode_perangkat')->ignore($lokasi?->id_lokasi, 'id_lokasi')],
            'latitude' => ['required', 'numeric', 'between:'.self::LAT[0].','.self::LAT[1]],
            'longitude' => ['required', 'numeric', 'between:'.self::LNG[0].','.self::LNG[1]],
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => ['exists:kategori_sampah,id_kategori'],
            'aktif' => ['sometimes', 'boolean'],
        ], [
            'kode_titik.unique' => 'Kode titik sudah digunakan.',
            'kode_perangkat.unique' => 'Kode perangkat sudah digunakan.',
            'latitude.between' => 'Koordinat berada di luar kawasan Telkom University.',
            'longitude.between' => 'Koordinat berada di luar kawasan Telkom University.',
        ]);

        $kategori = $v['kategori'];
        unset($v['kategori']);
        unset($v['aktif']);
        $v['status'] = $request->exists('aktif')
            ? ($request->boolean('aktif') ? 'aktif' : 'nonaktif')
            : ($lokasi?->status ?? 'aktif');

        return [$v, $kategori];
    }
}
