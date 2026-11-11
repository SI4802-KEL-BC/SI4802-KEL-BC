<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal belum dapat dimuat | PilahKu</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17221d;background:#f5f7f5}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px}
        .card{max-width:480px;background:#fff;border:1px solid #e4e9e5;border-radius:16px;padding:32px;text-align:center;box-shadow:0 6px 24px #253c2c0c}
        .icon{width:44px;height:44px;margin:0 auto 15px;display:grid;place-items:center;background:#fff0ed;color:#9f3b2d;border-radius:14px;font-size:20px}
        h1{font-size:20px;margin:0 0 9px}p{font-size:13px;color:#758079;line-height:1.6;margin:0 0 19px}
        a{display:inline-flex;background:#20764b;color:#fff;text-decoration:none;padding:11px 16px;border-radius:9px;font-size:13px;font-weight:650}
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">!</div>
        <h1>Jadwal belum dapat dimuat</h1>
        <p>Terjadi kendala saat mengambil data jadwal. Coba muat ulang halaman beberapa saat lagi.</p>
        <a href="{{ route('schedules.public') }}">Muat ulang jadwal</a>
    </main>
</body>
</html>
