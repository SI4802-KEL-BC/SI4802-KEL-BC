<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** Render teks/URL menjadi gambar QR (SVG murni PHP, tanpa imagick). */
class QrGambarService
{
    public function svg(string $isi, int $ukuran = 280): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($ukuran, 2),
            new SvgImageBackEnd,
        ));

        return $writer->writeString($isi);
    }
}
