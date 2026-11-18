{{--
    Bilah navigasi bersama untuk halaman yang tidak memakai layouts.app (beranda, panduan, jadwal, kategori, lokasi).
    Menyambungkan semua modul: tautan publik, menu pengguna, menu admin, serta Masuk/Keluar.
    Gaya ditulis mandiri (tanpa Tailwind) supaya tampil sama di halaman ber-CDN maupun ber-CSS sendiri.
--}}
@php
    $penggunaNav = auth()->user();
    $adminNav = strtolower((string) ($penggunaNav->peran ?? '')) === 'admin';
    $tautanNav = [
        ['Beranda', route('home'), '/'],
        ['Panduan', route('panduan.index'), 'panduan*'],
        ['Jadwal', route('schedules.public'), 'jadwal-pengangkutan*'],
    ];
    if ($penggunaNav) {
        $tautanNav[] = ['Pindai QR', route('pindai.index'), 'pindai*'];
        $tautanNav[] = ['Aktivitas Saya', route('aktivitas.index'), 'aktivitas*'];
        $tautanNav[] = ['Profil', route('profil.show'), 'profile*'];
    }
    if ($adminNav) {
        $tautanNav[] = ['Verifikasi', route('admin.verifikasi.index'), 'admin/verifikasi*'];
        $tautanNav[] = ['Aturan Reward Point', route('admin.aturan-reward.index'), 'admin/aturan-reward*'];
        $tautanNav[] = ['Kelola Jadwal', route('admin.schedules.index'), 'admin/jadwal-pengangkutan*'];
        $tautanNav[] = ['Kelola Lokasi', route('admin.lokasi.index'), 'admin/lokasi*'];
        $tautanNav[] = ['Kelola Kategori', route('kategori.index'), 'kategori*'];
    }
@endphp
<style>
    .pk-nav-global{position:sticky;top:0;z-index:60;display:flex;align-items:center;gap:6px;height:44px;padding:0 16px;margin:0;background:#0B2E1D;color:#fff;font:500 13px/1 system-ui,-apple-system,"Segoe UI",sans-serif;overflow-x:auto;white-space:nowrap;box-sizing:border-box}
    .pk-nav-global a,.pk-nav-global button{color:#d5e6dc;text-decoration:none;padding:7px 10px;border-radius:8px;border:0;background:none;font:inherit;cursor:pointer;width:auto;min-height:0}
    .pk-nav-global a:hover,.pk-nav-global button:hover{background:rgba(255,255,255,.1);color:#fff}
    .pk-nav-global a[aria-current="page"]{background:rgba(34,197,94,.25);color:#fff}
    .pk-nav-global .pk-nav-merek{font-weight:700;color:#fff;margin-right:6px}
    .pk-nav-global .pk-nav-kanan{margin-left:auto;display:flex;align-items:center;gap:6px}
    .pk-nav-global .pk-nav-nama{color:#9fb8aa;padding:0 4px}
    .pk-nav-global form{margin:0;display:inline}
    .pk-nav-global .pk-nav-utama{background:#16A34A;color:#fff}
    /* Halaman dengan sidebar "fixed" setinggi layar: geser ke bawah bilah ini. */
    .pk-nav-global ~ div > aside[class*="lg:fixed"]{top:44px}
</style>
<nav class="pk-nav-global" aria-label="Navigasi PilahKu">
    <a class="pk-nav-merek" href="{{ route('home') }}">PilahKu</a>
    @foreach ($tautanNav as [$labelNav, $urlNav, $polaNav])
        <a href="{{ $urlNav }}" @if (request()->is($polaNav)) aria-current="page" @endif>{{ $labelNav }}</a>
    @endforeach
    <span class="pk-nav-kanan">
        @if ($penggunaNav)
            <span class="pk-nav-nama">{{ $penggunaNav->nama_lengkap }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        @else
            <a class="pk-nav-utama" href="{{ route('login') }}">Masuk</a>
        @endif
    </span>
</nav>
