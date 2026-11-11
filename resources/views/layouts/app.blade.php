{{--
    Layout utama PilahKu (sidebar hijau tua + topbar putih), mengikuti mockup Figma.

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
        ['label' => 'Beranda', 'ikon' => 'beranda', 'url' => '#', 'pola' => 'beranda*', 'siap' => false],
        ['label' => 'Panduan Pemilahan', 'ikon' => 'buku', 'url' => '#', 'pola' => 'panduan*', 'siap' => false],
        ['label' => 'Jadwal Pengangkutan', 'ikon' => 'kalender', 'url' => '#', 'pola' => 'jadwal*', 'siap' => false],
        ['label' => 'Peta Tempat Sampah', 'ikon' => 'pin', 'url' => '#', 'pola' => 'lokasi*', 'siap' => false],
        ['label' => 'Pindai QR', 'ikon' => 'pindai', 'url' => url('/pindai'), 'pola' => 'pindai*', 'siap' => true],
        ['label' => 'Aktivitas Saya', 'ikon' => 'daftar', 'url' => url('/aktivitas'), 'pola' => 'aktivitas*', 'siap' => true],
        ['label' => 'Poin Saya', 'ikon' => 'koin', 'url' => '#', 'pola' => 'poin*', 'siap' => false],
        ['label' => 'Alur Pengelolaan', 'ikon' => 'alur', 'url' => '#', 'pola' => 'alur*', 'siap' => false],
    ];
    $menuAdmin = [
        ['label' => 'Verifikasi Manual', 'ikon' => 'perisai', 'url' => url('/admin/verifikasi'), 'pola' => 'admin/verifikasi*', 'siap' => true],
        ['label' => 'QR Tong Sampah', 'ikon' => 'qr', 'url' => url('/admin/qr-tong'), 'pola' => 'admin/qr-tong*', 'siap' => true],
    ];
    $polos = trim($__env->yieldContent('polos')) !== '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'PilahKu')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        dialog::backdrop { background: rgba(15, 42, 28, .45); }
    </style>
</head>
<body class="min-h-screen bg-[#F3F7F4] text-[#0F2A1C] antialiased">

@if ($polos)
    <main class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full @yield('lebar', 'max-w-md')">@yield('isi')</div>
    </main>
@else
    {{-- Lapisan gelap saat sidebar dibuka di HP --}}
    <div id="lapisan-sidebar" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden" onclick="toggleSidebar(false)"></div>

    {{-- SIDEBAR --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-[#0F3D26] text-white transition-transform lg:translate-x-0">
        <div class="flex items-center gap-3 px-6 pt-6 pb-8">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-[#16A34A] text-lg font-bold">P</span>
            <div class="leading-tight">
                <p class="text-base font-bold">PilahKu</p>
                <p class="text-xs text-white/60">Kampus Berkelanjutan</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-4">
            @foreach ($menuUtama as $item)
                @php $aktif = request()->is($item['pola']); @endphp
                <a href="{{ $item['url'] }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                          {{ $aktif ? 'bg-[#16A34A]/25 text-white' : 'text-white/75 hover:bg-white/10 hover:text-white' }}
                          {{ $item['siap'] ? '' : ($aktif ? 'pointer-events-none' : 'pointer-events-none opacity-45') }}"
                   @if (! $item['siap']) aria-disabled="true" title="Belum tersedia" @endif>
                    <span class="{{ $aktif ? 'text-[#4ADE80]' : 'text-white/60' }}">@include('partials.ikon', ['n' => $item['ikon'], 'c' => 'h-[18px] w-[18px]'])</span>
                    {{ $item['label'] }}
                </a>
            @endforeach

            @if ($adalahAdmin)
                <p class="px-3 pt-6 pb-2 text-[11px] font-semibold tracking-wider text-white/45">ADMIN</p>
                @foreach ($menuAdmin as $item)
                    @php $aktif = request()->is($item['pola']); @endphp
                    <a href="{{ $item['url'] }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                              {{ $aktif ? 'bg-[#16A34A]/25 text-white' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                        <span class="{{ $aktif ? 'text-[#4ADE80]' : 'text-white/60' }}">@include('partials.ikon', ['n' => $item['ikon'], 'c' => 'h-[18px] w-[18px]'])</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="p-4">
            <div class="flex items-center gap-3 rounded-2xl bg-white/[.07] p-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#16A34A] text-sm font-semibold">{{ $inisial ?: 'P' }}</span>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-semibold">{{ $namaPengguna }}</p>
                    <p class="truncate text-xs text-white/55">{{ $adalahAdmin ? 'Admin' : 'Civitas Academica' }}@isset($saldoPoin) · {{ number_format($saldoPoin, 0, ',', '.') }} poin @endisset</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- AREA KONTEN --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-[#E1EAE4] bg-white/95 px-4 backdrop-blur sm:px-8">
            <button type="button" class="-ml-1 rounded-lg p-2 text-[#5B6B62] hover:bg-[#F3F7F4] lg:hidden" onclick="toggleSidebar(true)" aria-label="Buka menu">
                @include('partials.ikon', ['n' => 'menu'])
            </button>

            <nav class="min-w-0 flex-1 truncate text-sm text-[#5B6B62]">
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @else
                    <span class="font-semibold text-[#0F2A1C]">@yield('halaman')</span>
                @endif
            </nav>

            @stack('navbar-aksi')

            @isset($saldoPoin)
                <span class="hidden items-center gap-2 rounded-full bg-[#E8F7EE] px-3 py-1.5 text-sm font-semibold text-[#15803D] sm:inline-flex">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#16A34A] text-[11px] text-white">+</span>
                    {{ number_format($saldoPoin, 0, ',', '.') }} poin
                </span>
            @endisset
            <span class="grid h-10 w-10 place-items-center rounded-full bg-[#16A34A] text-sm font-semibold text-white" title="{{ $namaPengguna }}">{{ $inisial ?: 'P' }}</span>
        </header>

        <main class="mx-auto w-full @yield('lebar', 'max-w-6xl') px-4 py-8 sm:px-8">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-[#BBE5CA] bg-[#E8F7EE] px-5 py-4 text-sm text-[#15803D]">
                    @include('partials.ikon', ['n' => 'centang', 'c' => 'mt-0.5 h-5 w-5 shrink-0'])
                    <span>{{ session('status') }}</span>
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
</script>
@stack('skrip')
</body>
</html>
