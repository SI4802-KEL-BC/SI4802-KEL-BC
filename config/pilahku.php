<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PBI-13 · Aktivitas Pemilahan
    |--------------------------------------------------------------------------
    */

    // Masa berlaku QR ID (detik). QR hanya bisa dipakai sekali.
    'qr_ttl' => (int) env('PILAHKU_QR_TTL', 120),

    // Batas waktu sesi menunggu foto setelah QR dipindai (detik, default 5 menit).
    'session_ttl' => (int) env('PILAHKU_SESSION_TTL', 300),

    // Skor keyakinan di bawah nilai ini => hasil tahap 1 "Ragu" (verifikasi manual Admin).
    'ambang_skor' => (float) env('PILAHKU_AMBANG_SKOR', 0.70),

    // Token perangkat tempat sampah (header X-Device-Token), pisahkan dengan koma di .env.
    'device_tokens' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PILAHKU_DEVICE_TOKENS', 'dev-token-local'))
    ))),

    // Pemetaan label model identifikasi => nama_kategori di tabel kategori_sampah.
    'label_kategori' => [
        'plastic' => 'Plastik',
        'paper' => 'Kertas',
        'cardboard' => 'Kertas',
        'organik' => 'Organik',
        'biodegradable' => 'Organik',
    ],

];
