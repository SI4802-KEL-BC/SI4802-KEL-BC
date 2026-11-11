<?php

namespace App\Http\Controllers;

use App\Models\KategoriSampah;
use App\Models\PanduanPemilahan;
use Illuminate\Database\QueryException;

class PanduanPemilahanController extends Controller
{
    public function index()
    {
        try {
            $kategoris = KategoriSampah::query()
                ->where('status', 'aktif')
                ->orderBy('nama_kategori')
                ->get();

            try {
                $panduanByKategori = PanduanPemilahan::query()
                    ->where('status', 'aktif')
                    ->whereIn('id_kategori', $kategoris->pluck('id_kategori'))
                    ->orderBy('judul')
                    ->get()
                    ->groupBy('id_kategori');
                $panduanTableError = false;
            } catch (QueryException) {
                // Informasi PBI-4 tetap bisa ditampilkan jika tabel panduan belum dimigrasikan.
                $panduanByKategori = collect();
                $panduanTableError = true;
            }

            return view('panduan.index', compact('kategoris', 'panduanByKategori', 'panduanTableError'));
        } catch (QueryException) {
            return response()->view('panduan.index', [
                'kategoris' => collect(),
                'panduanByKategori' => collect(),
                'panduanTableError' => false,
                'databaseError' => true,
            ], 503);
        }
    }

    public function showKategori(string $id)
    {
        try {
            $kategori = KategoriSampah::query()
                ->where('id_kategori', $id)
                ->where('status', 'aktif')
                ->first();

            if (! $kategori) {
                return response()->view('panduan.detail', [
                    'kategori' => null,
                    'panduan' => collect(),
                    'databaseError' => false,
                ], 404);
            }

            try {
                $panduan = $kategori->panduanPemilahan()
                    ->where('status', 'aktif')
                    ->orderBy('judul')
                    ->get();
                $panduanTableError = false;
            } catch (QueryException) {
                $panduan = collect();
                $panduanTableError = true;
            }

            return view('panduan.detail', compact('kategori', 'panduan', 'panduanTableError'));
        } catch (QueryException) {
            return response()->view('panduan.detail', [
                'kategori' => null,
                'panduan' => collect(),
                'panduanTableError' => false,
                'databaseError' => true,
            ], 503);
        }
    }

    public function show(string $slug)
    {
        try {
            $panduan = PanduanPemilahan::with('kategori')
                ->where('slug', $slug)
                ->where('status', 'aktif')
                ->whereHas('kategori', fn ($query) => $query->where('status', 'aktif'))
                ->first();

            if (! $panduan) {
                return response()->view('panduan.show', [
                    'panduan' => null,
                    'databaseError' => false,
                ], 404);
            }

            return view('panduan.show', ['panduan' => $panduan, 'databaseError' => false]);
        } catch (QueryException) {
            return response()->view('panduan.show', [
                'panduan' => null,
                'databaseError' => true,
            ], 503);
        }
    }
}
