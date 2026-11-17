<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · PilahKu</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root { --hijau: #16a34a; --tua: #123d26; --bg: #eef4f0; --garis: #dbe5de; --teks: #1b2a20; --abu: #6b7a70; --merah: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; display: flex; min-height: 100vh; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: var(--bg); color: var(--teks); }
        .sb { width: 230px; flex-shrink: 0; background: var(--tua); color: #fff; padding: 18px 12px; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; margin: 0 6px 22px; }
        .brand i { width: 30px; height: 30px; border-radius: 50%; background: #22c55e; display: grid; place-items: center; font-style: normal; }
        .brand small { display: block; font-weight: 400; font-size: 11px; opacity: .7; }
        .sb a, .sb .off { display: block; padding: 9px 12px; margin-bottom: 2px; border-radius: 8px; font-size: 14px; color: #fff; text-decoration: none; }
        .sb a.on { background: rgba(255,255,255,.15); font-weight: 600; }
        .sb .off { opacity: .4; cursor: not-allowed; }
        .main { flex: 1; min-width: 0; }
        .top { background: #fff; border-bottom: 1px solid var(--garis); padding: 12px 28px; font-size: 13px; color: var(--abu); }
        .top b { color: var(--teks); }
        .wrap { padding: 24px 28px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 18px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 16px; margin: 0; }
        .muted { color: var(--abu); font-size: 13px; }
        .split { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
        .split.with-panel { grid-template-columns: minmax(0, 1fr) 380px; }
        @media (max-width: 1000px) { .split.with-panel { grid-template-columns: 1fr; } .sb { display: none; } }
        .filters { display: grid; grid-template-columns: 1fr 150px 150px auto; gap: 8px; margin-bottom: 12px; }
        input[type=text], input[type=number], input[type=search], select { width: 100%; padding: 9px 10px; border: 1px solid var(--garis); border-radius: 8px; font-size: 14px; background: #fff; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid var(--garis); border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
        th, td { padding: 11px 12px; text-align: left; border-bottom: 1px solid #edf2ee; font-size: 14px; vertical-align: top; }
        th { font-size: 11px; text-transform: uppercase; color: var(--abu); background: #f7faf8; }
        tr.pilih td { background: #e9f6ee; }
        .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; }
        .badge { padding: 2px 10px; border-radius: 999px; font-size: 12px; }
        .badge.aktif { background: #dcfce7; color: #166534; }
        .badge.nonaktif { background: #e5e7eb; color: #4b5563; }
        .btn { display: inline-block; padding: 9px 16px; background: var(--hijau); color: #fff; border: 0; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-light { background: #fff; color: var(--teks); border: 1px solid var(--garis); }
        .aksi { display: flex; gap: 12px; white-space: nowrap; }
        .aksi form { margin: 0; }
        .lnk { background: none; border: 0; padding: 0; font-size: 13px; font-weight: 600; color: var(--hijau); cursor: pointer; text-decoration: none; }
        .lnk.merah { color: var(--merah); }
        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert.ok { background: #dcfce7; color: #166534; }
        .alert.err { background: #fee2e2; color: #991b1b; }
        .alert ul { margin: 0; padding-left: 18px; }
        .panel { background: #fff; border: 1px solid var(--garis); border-radius: 12px; padding: 18px; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .panel-head a { font-size: 22px; line-height: 1; color: var(--abu); text-decoration: none; }
        .panel label { display: block; font-size: 12px; font-weight: 600; margin: 12px 0 4px; }
        .dua { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        #peta-mini { height: 190px; border-radius: 8px; border: 1px solid var(--garis); margin-top: 12px; }
        .chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; margin: 0 6px 6px 0; border: 1px solid var(--garis); border-radius: 999px; font-size: 13px; font-weight: 400 !important; cursor: pointer; }
        .cek { display: flex !important; align-items: center; gap: 8px; font-weight: 400 !important; }
        .panel .aksi-form { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
        #peta-daftar { height: 300px; border-radius: 10px; border: 1px solid var(--garis); margin-bottom: 14px; }

    </style>
</head>
<body>
    <nav class="sb">
        <div class="brand"><i>P</i><div>PilahKu<small>Panel Admin</small></div></div>
        <span class="off" title="Belum tersedia (sprint lain)">Dashboard</span>
        <span class="off" title="Belum tersedia (sprint lain)">Validasi Aktivitas</span>
        <span class="off" title="Belum tersedia (sprint lain)">Aturan Reward Point</span>
        <span class="off" title="Belum tersedia (sprint lain)">Kategori Sampah</span>
        <span class="off" title="Belum tersedia (sprint lain)">Jadwal Pengangkutan</span>
        <a class="on" href="{{ route('admin.lokasi.index') }}">Lokasi Tempat Sampah</a>
        <span class="off" title="Belum tersedia (sprint lain)">Alur Pengelolaan</span>
    </nav>

    <div class="main">
        <div class="top">Admin / <b>Lokasi Tempat Sampah</b></div>
        <div class="wrap">
            @if (session('status'))
                <div class="alert ok">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert err">
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>

</html>
