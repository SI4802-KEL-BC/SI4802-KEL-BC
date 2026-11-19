<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PBI-13 · Aktivitas Pemilahan
    |--------------------------------------------------------------------------
    */

    // Alamat dasar yang dikodekan di QR tong (QR berisi {qr_base_url}/pindai/{kode_perangkat}).
    // Kosong = pakai alamat situs saat ini. Isi untuk uji dari HP (mis. http://192.168.1.10:8000) atau domain produksi.
    'qr_base_url' => env('PILAHKU_QR_BASE_URL'),

    // Batas waktu sesi menunggu foto setelah QR tong dipindai (detik, default 5 menit).
    'session_ttl' => (int) env('PILAHKU_SESSION_TTL', 300),

    // Skor keyakinan di bawah nilai ini => hasil tahap 1 "Ragu" (verifikasi manual Admin).
    'ambang_skor' => (float) env('PILAHKU_AMBANG_SKOR', 0.70),

    // Token perangkat tempat sampah (header X-Device-Token), pisahkan dengan koma di .env.
    'device_tokens' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PILAHKU_DEVICE_TOKENS', 'dev-token-local'))
    ))),

    // Hasil tetap MockVerifikasiOtomatisService (simulasi model klasifikasi di development).
    'mock' => [
        'label' => env('PILAHKU_MOCK_LABEL', 'plastic'),
        'skor' => (float) env('PILAHKU_MOCK_SKOR', 0.92),
    ],

    // Pemetaan label model identifikasi => nama_kategori di tabel kategori_sampah.
    'label_kategori' => [
        'plastic' => 'Plastik',
        'paper' => 'Kertas',
        'cardboard' => 'Kertas',
        'organik' => 'Organik',
        'biodegradable' => 'Organik',
        'metal' => 'Logam',
        'glass' => 'Kaca',
    ],

];
