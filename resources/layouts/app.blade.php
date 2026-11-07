<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'PilahKu' }} - Telkom University</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="h-full font-sans text-slate-800 antialiased selection:bg-emerald-600 selection:text-white bg-slate-50 overflow-hidden">

@php
    $u = auth()->user();

    // Menu untuk masing-masing role
    $menu = $u->isStaff()
        ? array_filter([
            ['Validasi Aktivitas', 'admin.validasi.index', 'admin/validasi*', 'check-circle'],
            $u->isAdmin() ? ['Aturan Reward Point', 'admin.reward.index', 'admin/reward*', 'gift'] : null,
            $u->isAdmin() ? ['Kategori Sampah', 'admin.kategori.index', 'admin/kategori*', 'tag'] : null,
            $u->isAdmin() ? ['Jadwal Pengangkutan', 'admin.jadwal.index', 'admin/jadwal*', 'calendar'] : null,
            $u->isAdmin() ? ['Lokasi Tempat Sampah', 'admin.lokasi.index', 'admin/lokasi*', 'map-pin'] : null,
        ])
        : [
            ['Panduan Pemilahan', 'panduan.index', 'panduan*', 'book-open'],
            ['Jadwal Pengangkutan', 'jadwal.index', 'jadwal*', 'calendar'],
            ['Peta Tempat Sampah', 'peta.index', 'peta*', 'map'],
            ['Aktivitas Saya', 'aktivitas.index', 'aktivitas*', 'recycle'],
            ['Poin Saya', 'poin.index', 'poin*', 'award'],
            ['Alur Pengelolaan', 'alur.index', 'alur*', 'git-branch'],
        ];

    $saldoPoin = $u->isCivitas()
        ? app(\App\Services\PointEngine::class)->saldo($u)
        : 0;
@endphp

<div class="flex h-full w-full overflow-hidden">

    <!-- Desktop Sidebar -->
    <aside class="hidden w-68 shrink-0 flex-col bg-slate-950 border-r border-slate-800/80 text-white lg:flex h-full">

        <!-- Logo Header -->
        <div class="p-6 border-b border-slate-800/60">
            <a href="{{ route('profil.show') }}" class="group flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 shadow-md shadow-emerald-950/50 border border-white/20 transition-transform group-hover:scale-105">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/>
                        <path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/>
                        <path d="m14 16-3 3 3 3"/>
                        <path d="M8.293 13.596 11 9l2.707 4.596"/>
                        <path d="m9.67 4.14 1.77-3.07a1.83 1.83 0 0 1 3.12 0l4.246 7.36"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-1.5 font-bold tracking-tight text-white text-lg">
                        Pilah<span class="text-emerald-400">Ku</span>
                    </div>
                    <div class="text-[11px] font-medium tracking-wider uppercase text-slate-400">
                        Telkom University
                    </div>
                </div>
            </a>
        </div>

        <!-- Role Badge -->
        <div class="px-6 py-3 bg-slate-900/60 border-b border-slate-800/50 flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-200">
                Panel Sistem
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/25 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-300 border border-emerald-400/40">
                {{ $u->peran }}
            </span>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 space-y-1.5 p-4 overflow-y-auto">
            @foreach ($menu as [$label, $route, $pattern, $icon])
                @php
                    $active = request()->is($pattern);
                @endphp
                <a href="{{ route($route) }}" class="group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $active ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/40' : 'text-slate-100 hover:bg-emerald-950/50 hover:text-white' }}">
                    <!-- HOME -->
                    @if ($icon === 'home')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    <!-- BOOK -->
                    @elseif ($icon === 'book-open')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    <!-- CALENDAR -->
                    @elseif ($icon === 'calendar')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    <!-- MAP -->
                    @elseif ($icon === 'map' || $icon === 'map-pin')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    <!-- RECYCLE -->
                    @elseif ($icon === 'recycle')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    <!-- AWARD -->
                    @elseif ($icon === 'award')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    <!-- GIT BRANCH -->
                    @elseif ($icon === 'git-branch')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    <!-- CHECK CIRCLE -->
                    @elseif ($icon === 'check-circle')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    <!-- GIFT -->
                    @elseif ($icon === 'gift')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V6a2 2 0 10-2 2h2zm0 0v4l6-3-6-1zm0 4l-6-3 6-1m-7 4v6a2 2 0 002 2h10a2 2 0 002-2v-6H5z"/>
                        </svg>
                    <!-- TAG -->
                    @elseif ($icon === 'tag')
                        <svg class="h-5 w-5 shrink-0 transition-colors {{ $active ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    @else
                        <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    @endif
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-900/40">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-700 to-teal-600 font-bold text-white shadow-xs border border-white/10 overflow-hidden">
                    @if ($u->foto_profil)
                        <img src="{{ asset('storage/' . $u->foto_profil) }}" class="h-full w-full object-cover" alt="{{ $u->nama_lengkap }}">
                    @else
                        {{ strtoupper(substr($u->nama_lengkap, 0, 2)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-bold text-white">
                        {{ $u->nama_lengkap }}
                    </p>
                    <p class="truncate text-[11px] text-slate-300 font-medium">
                        {{ $u->email_kampus }}
                    </p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar -->
    <div id="mobile-sidebar" class="fixed inset-0 z-50 hidden lg:hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" onclick="toggleMobileSidebar()"></div>

        <!-- Drawer -->
        <div class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-slate-950 text-white flex flex-col shadow-2xl z-10">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 font-bold text-white shadow">
                        P
                    </div>
                    <div>
                        <span class="font-bold text-base text-white">
                            Pilah<span class="text-emerald-400">Ku</span>
                        </span>
                        <span class="block text-[10px] text-slate-400">
                            Telkom University
                        </span>
                    </div>
                </div>
                <button type="button" onclick="toggleMobileSidebar()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <nav class="flex-1 space-y-1 p-4 overflow-y-auto">
                @foreach ($menu as [$label, $route, $pattern, $icon])
                    @php
                        $active = request()->is($pattern);
                    @endphp
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition-all {{ $active ? 'bg-emerald-600 text-white font-bold' : 'text-slate-100 hover:bg-slate-800' }}">
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>

            <!-- Mobile User Footer -->
            <div class="p-4 border-t border-slate-800 bg-slate-900/60">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-white">
                            {{ $u->nama_lengkap }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ ucfirst($u->peran) }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-rose-400 hover:underline">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-1 flex-col min-w-0 h-full overflow-y-auto">

        <!-- Top Header -->
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/95 px-4 sm:px-6 lg:px-8 backdrop-blur-md shadow-2xs">
            <div class="flex items-center gap-3">
                <!-- Mobile Menu -->
                <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden rounded-xl p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Page Title -->
                <div>
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                        {{ $title ?? 'PilahKu' }}
                    </h1>
                </div>
            </div>

            <!-- Header Right -->
            <div class="flex items-center gap-3 sm:gap-4">
                @if ($u->isCivitas())
                    <!-- Poin -->
                    <a href="{{ route('poin.index') }}" class="group flex items-center gap-2 rounded-full bg-emerald-50 hover:bg-emerald-100/80 px-3.5 py-1.5 text-xs font-bold text-emerald-900 border border-emerald-300/80 shadow-2xs transition-all hover:scale-102" title="Saldo Poin Anda">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white shadow-2xs">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span class="font-extrabold text-emerald-950 text-sm">
                            {{ number_format($saldoPoin, 0, ',', '.') }}
                        </span>
                        <span class="text-[10px] text-emerald-700 uppercase font-black tracking-wider">
                            Poin
                        </span>
                    </a>

                    <!-- Profile -->
                    <a href="{{ route('profil.show') }}" class="flex items-center gap-2.5 rounded-xl px-2.5 py-1.5 text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 border border-transparent hover:border-emerald-200 transition-colors" title="Profil Saya">
                        <div class="h-8 w-8 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-xs border border-emerald-500/30 overflow-hidden shrink-0">
                            @if ($u->foto_profil)
                                <img src="{{ asset('storage/' . $u->foto_profil) }}" class="h-full w-full object-cover" alt="">
                            @else
                                {{ strtoupper(substr($u->nama_lengkap, 0, 2)) }}
                            @endif
                        </div>
                        <span class="hidden md:inline text-xs font-bold text-slate-800">
                            {{ $u->nama_panggilan ?? $u->nama_lengkap }}
                        </span>
                    </a>
                @endif

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-2xs hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-colors" title="Keluar dari sistem">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="hidden sm:inline">
                            Keluar
                        </span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
            @include('partials.flash')
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 max-w-7xl mx-auto">
                <div>
                    &copy; {{ date('Y') }} <b>PilahKu</b> &bull; Telkom University Green Campus Initiative.
                </div>
                <div class="text-slate-400">
                    Sistem Informasi Kelompok BC (SI-48-02)
                </div>
            </div>
        </footer>
    </div>
</div>

<script>
    function toggleMobileSidebar() {
        const drawer = document.getElementById('mobile-sidebar');
        if (drawer.classList.contains('hidden')) {
            drawer.classList.remove('hidden');
        } else {
            drawer.classList.add('hidden');
        }
    }
</script>

</body>
</html>