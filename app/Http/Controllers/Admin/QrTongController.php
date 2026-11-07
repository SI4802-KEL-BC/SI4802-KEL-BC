<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LokasiTempatSampah;
use App\Services\PindaiTongService;
use App\Services\QrGambarService;
use Illuminate\View\View;

/** Halaman Admin untuk melihat dan mencetak QR yang ditempel di tiap tong sampah. */
class QrTongController extends Controller
{
    public function index(PindaiTongService $pindai, QrGambarService $gambar): View
    {
        $daftar = LokasiTempatSampah::with('kategoriDiterima')
            ->orderBy('kode_perangkat')
            ->get()
            ->map(function (LokasiTempatSampah $lokasi) use ($pindai, $gambar) {
                $url = $pindai->urlQr($lokasi);

                return ['lokasi' => $lokasi, 'url' => $url, 'svg' => $gambar->svg($url, 240)];
            });

        return view('admin.qr-tong.index', ['daftar' => $daftar]);
    }
}
