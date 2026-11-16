{{--
    Layout utama PilahKu: sidebar hijau tua (gradasi) + topbar putih transparan.

    Section yang dipakai halaman:
        @section('judul', 'Judul Tab')              → <title>
        @section('breadcrumb') ... @endsection      → isi breadcrumb di topbar (opsional)
        @section('halaman', 'Nama Halaman')         → dipakai bila breadcrumb kosong
        @section('isi') ... @endsection             → konten (wajib)
        @section('lebar', 'max-w-6xl')              → lebar konten (opsional)
        @section('polos', '1')                      → tanpa sidebar & topbar (mis. halaman login)
        @push('navbar-aksi') ... @endpush           → elemen tambahan di kanan topbar
        @push('skrip') <script>…</script> @endpush  → skrip di akhir <body>

    Variabel opsional (boleh dibagikan lewat View::share / view composer):
        $saldoPoin  → angka saldo poin pengguna, tampil sebagai pil di topbar.
--}}
@php
    $pengguna = auth()->user();
    $namaPengguna = $pengguna->nama_lengkap ?? 'Pengguna';
    $inisial = collect(explode(' ', trim($namaPengguna)))->filter()->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
    $adalahAdmin = strtolower((string) ($pengguna->peran ?? '')) === 'admin'; // selaras dengan middleware role:admin

    // Menu sidebar. 'siap' = halaman sudah dibuat; selain itu tampil redup (milik PBI lain).
    $menuUtama = [
        ['label' => 'Beranda', 'ikon' => 'beranda', 'url' => route('home'), 'pola' => '/', 'siap' => true],
        ['label' => 'Panduan Pemilahan', 'ikon' => 'buku', 'url' => route('panduan.index'), 'pola' => 'panduan*', 'siap' => true],
        ['label' => 'Jadwal Pengangkutan', 'ikon' => 'kalender', 'url' => route('schedules.public'), 'pola' => 'jadwal-pengangkutan*', 'siap' => true],
        ['label' => 'Peta Tempat Sampah', 'ikon' => 'pin', 'url' => '#', 'pola' => 'lokasi*', 'siap' => false],
        ['label' => 'Pindai QR', 'ikon' => 'pindai', 'url' => url('/pindai'), 'pola' => 'pindai*', 'siap' => true],
        ['label' => 'Aktivitas Saya', 'ikon' => 'daftar', 'url' => url('/aktivitas'), 'pola' => 'aktivitas*', 'siap' => true],
        ['label' => 'Poin Saya', 'ikon' => 'koin', 'url' => '#', 'pola' => 'poin*', 'siap' => false],
        ['label' => 'Alur Pengelolaan', 'ikon' => 'alur', 'url' => '#', 'pola' => 'alur*', 'siap' => false],
    ];
    $menuAdmin = [
        ['label' => 'Verifikasi Manual', 'ikon' => 'perisai', 'url' => url('/admin/verifikasi'), 'pola' => 'admin/verifikasi*', 'siap' => true],
        ['label' => 'QR Tong Sampah', 'ikon' => 'qr', 'url' => url('/admin/qr-tong'), 'pola' => 'admin/qr-tong*', 'siap' => true],
        ['label' => 'Kelola Jadwal', 'ikon' => 'kalender', 'url' => route('admin.schedules.index'), 'pola' => 'admin/jadwal-pengangkutan*', 'siap' => true],
        ['label' => 'Kelola Lokasi', 'ikon' => 'pin', 'url' => route('admin.lokasi.index'), 'pola' => 'admin/lokasi*', 'siap' => true],
        ['label' => 'Kelola Kategori', 'ikon' => 'sampah', 'url' => route('kategori.index'), 'pola' => 'kategori*', 'siap' => true],
    ];
    $polos = trim($__env->yieldContent('polos')) !== '';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B2E1D">
    <title>@yield('judul', 'PilahKu')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; font-feature-settings: 'cv11', 'ss01', 'ss03'; }
        dialog::backdrop { background: rgba(11, 46, 29, .45); backdrop-filter: blur(2px); }
        ::selection { background: #BBF7D0; color: #0B2E1D; }
        #sidebar nav::-webkit-scrollbar { width: 6px; }
        #sidebar nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .12); border-radius: 9999px; }
    </style>
</head>
<body class="min-h-full bg-[#F5F7F6] text-[#0F2A1C] antialiased">

@if ($polos)
    <main class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full @yield('lebar', 'max-w-md')">@yield('isi')</div>
    </main>
@else
    {{-- Lapisan gelap saat sidebar dibuka di HP --}}
    <div id="lapisan-sidebar" class="fixed inset-0 z-30 hidden bg-[#0B2E1D]/50 backdrop-blur-[2px] lg:hidden" onclick="toggleSidebar(false)"></div>

    {{-- SIDEBAR --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[260px] -translate-x-full flex-col bg-gradient-to-b from-[#0E3A25] to-[#0A2C1C] text-white shadow-xl shadow-black/10 transition-transform duration-200 lg:translate-x-0 lg:shadow-none">
        {{-- Merek --}}
        <div class="flex h-16 items-center gap-3 border-b border-white/[.06] px-5">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-[#22C55E] to-[#15803D] shadow-inner shadow-white/20 ring-1 ring-white/10">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
            </span>
            <div class="leading-tight">
                <p class="text-[15px] font-semibold tracking-tight">PilahKu</p>
                <p class="text-[11px] text-white/50">Kampus Berkelanjutan</p>
            </div>
            <button type="button" class="ml-auto rounded-lg p-1.5 text-white/60 hover:bg-white/10 hover:text-white lg:hidden" onclick="toggleSidebar(false)" aria-label="Tutup menu">
                @include('partials.ikon', ['n' => 'silang', 'c' => 'h-5 w-5'])
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5">
            <p class="px-3 pb-2 text-[11px] font-semibold tracking-[.12em] text-white/35">MENU</p>
            <div class="space-y-0.5">
                @foreach ($menuUtama as $item)
                    @php $aktif = request()->is($item['pola']); @endphp
                    <a href="{{ $item['url'] }}"
                       class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[13.5px] font-medium transition-colors
                              {{ $aktif ? 'bg-[#16A34A]/25 text-white' : 'text-white/70 hover:bg-white/[.06] hover:text-white' }}
                              {{ $item['siap'] ? '' : 'pointer-events-none' }}"
                       @if (! $item['siap']) aria-disabled="true" title="Belum tersedia" @endif>
                        @if ($aktif)
                            <span class="absolute inset-y-1.5 left-0 w-[3px] rounded-r-full bg-[#4ADE80]" aria-hidden="true"></span>
                        @endif
                        <span class="{{ $aktif ? 'text-[#4ADE80]' : ($item['siap'] ? 'text-white/55 group-hover:text-white/80' : 'text-white/25') }}">@include('partials.ikon', ['n' => $item['ikon'], 'c' => 'h-[18px] w-[18px]'])</span>
                        <span class="truncate {{ $item['siap'] ? '' : 'text-white/35' }}">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            @if ($adalahAdmin)
                <p class="px-3 pt-7 pb-2 text-[11px] font-semibold tracking-[.12em] text-white/35">ADMIN</p>
                <div class="space-y-0.5">
                    @foreach ($menuAdmin as $item)
                        @php $aktif = request()->is($item['pola']); @endphp
                        <a href="{{ $item['url'] }}"
                           class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[13.5px] font-medium transition-colors
                                  {{ $aktif ? 'bg-[#16A34A]/25 text-white' : 'text-white/70 hover:bg-white/[.06] hover:text-white' }}">
                            @if ($aktif)
                                <span class="absolute inset-y-1.5 left-0 w-[3px] rounded-r-full bg-[#4ADE80]" aria-hidden="true"></span>
                            @endif
                            <span class="{{ $aktif ? 'text-[#4ADE80]' : 'text-white/55 group-hover:text-white/80' }}">@include('partials.ikon', ['n' => $item['ikon'], 'c' => 'h-[18px] w-[18px]'])</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </nav>

        {{-- Profil --}}
        <div class="border-t border-white/[.06] p-3">
            <a href="{{ route('profil.show') }}" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-white/[.06]" title="Lihat profil">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/10 text-[13px] font-semibold ring-1 ring-white/15">{{ $inisial ?: 'P' }}</span>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-[13px] font-semibold">{{ $namaPengguna }}</p>
                    <p class="truncate text-[11px] text-white/50">{{ $adalahAdmin ? 'Admin' : 'Civitas Academica' }}@isset($saldoPoin) · {{ number_format($saldoPoin, 0, ',', '.') }} poin @endisset</p>
                </div>
            </a>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-[13px] font-medium text-white/70 hover:bg-white/[.06] hover:text-white">Keluar</button>
                </form>
            @endauth
        </div>
    </aside>

    {{-- AREA KONTEN --}}
    <div class="lg:pl-[260px]">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-[#E4EBE7] bg-white/80 px-4 backdrop-blur-md sm:px-8">
            <button type="button" class="-ml-1 rounded-lg p-2 text-[#5B6B62] hover:bg-[#F1F5F3] lg:hidden" onclick="toggleSidebar(true)" aria-label="Buka menu">
                @include('partials.ikon', ['n' => 'menu'])
            </button>

            <nav class="min-w-0 flex-1 truncate text-[13px] text-[#6B7A72]" aria-label="Breadcrumb">
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @else
                    <span class="font-medium text-[#0F2A1C]">@yield('halaman')</span>
                @endif
            </nav>

            @stack('navbar-aksi')

            @isset($saldoPoin)
                <span class="hidden items-center gap-2 rounded-full bg-[#ECFDF3] px-3 py-1.5 text-sm font-semibold text-[#15803D] ring-1 ring-[#BBF7D0] sm:inline-flex">
                    @include('partials.ikon', ['n' => 'koin', 'c' => 'h-4 w-4'])
                    {{ number_format($saldoPoin, 0, ',', '.') }} poin
                </span>
            @endisset

            <div class="flex items-center gap-3 border-l border-[#E4EBE7] pl-3 sm:pl-4">
                <div class="hidden text-right leading-tight sm:block">
                    <p class="text-[13px] font-semibold">{{ $namaPengguna }}</p>
                    <p class="text-[11px] text-[#6B7A72]">{{ $adalahAdmin ? 'Admin' : 'Civitas Academica' }}</p>
                </div>
                <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-[#22C55E] to-[#15803D] text-[13px] font-semibold text-white ring-2 ring-white shadow-sm" title="{{ $namaPengguna }}">{{ $inisial ?: 'P' }}</span>
            </div>
        </header>

        <main class="mx-auto w-full @yield('lebar', 'max-w-6xl') px-4 py-8 sm:px-8 lg:py-10">
            @if (session('status'))
                <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm text-[#166534] shadow-sm">
                    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#16A34A] text-white">@include('partials.ikon', ['n' => 'centang', 'c' => 'h-3 w-3'])</span>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @yield('isi')
        </main>
    </div>
@endif

<script>
    function toggleSidebar(buka) {
        document.getElementById('sidebar')?.classList.toggle('-translate-x-full', !buka);
        document.getElementById('lapisan-sidebar')?.classList.toggle('hidden', !buka);
    }
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggleSidebar(false); });
</script>
@stack('skrip')
</body>
</html>
