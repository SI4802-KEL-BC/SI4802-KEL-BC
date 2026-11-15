<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Pemilahan Sampah - PilahKu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-[#f4f8f5] text-slate-800">
<div class="min-h-screen lg:flex">
    <aside class="bg-[#0e422e] text-white lg:fixed lg:inset-y-0 lg:flex lg:w-56 lg:flex-col">
        <a href="{{ route('home') }}" class="block border-b border-white/10 px-6 py-5 text-xl font-extrabold tracking-tight">PilahKu</a>
        <nav class="flex gap-2 overflow-x-auto px-3 py-4 lg:flex-1 lg:flex-col" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm text-emerald-100 hover:bg-white/10"><span aria-hidden="true">&#8962;</span> Beranda</a>
            <a href="{{ route('panduan.index') }}" aria-current="page" class="flex shrink-0 items-center gap-3 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-300" aria-hidden="true"></span> Panduan Pemilahan</a>
        </nav>
        <p class="hidden px-6 pb-6 text-xs leading-5 text-emerald-100/70 lg:block">Pilah dengan benar, mulai dari rumah.</p>
    </aside>

    <main class="min-w-0 flex-1 px-5 py-7 sm:px-8 lg:ml-56 lg:px-10 lg:py-8" x-data="panduanPage()">
        <div class="mb-7 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Panduan Pemilahan</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">Panduan Pemilahan Sampah</h1>
                <p class="mt-1 text-sm text-slate-500">Pelajari cara memilah dan menangani sampah sesuai kategorinya.</p>
            </div>
            <label class="flex w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:max-w-xs">
                <span class="text-emerald-600" aria-hidden="true">&#9679;</span>
                <span class="sr-only">Cari jenis sampah</span>
                <input type="search" x-model="search" @input="filterCards" placeholder="Cari jenis sampah..." class="w-full border-0 bg-transparent text-sm outline-none placeholder:text-slate-400" autocomplete="off">
            </label>
        </div>

        @if (!empty($databaseError) && $databaseError)
            <section class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                <h2 class="font-bold">Data kategori belum dapat dimuat</h2>
                <p class="mt-1">Ada kendala koneksi ke database. Periksa koneksi lalu coba muat ulang halaman.</p>
                <a href="{{ route('panduan.index') }}" class="mt-2 inline-block font-bold underline">Coba lagi</a>
            </section>
        @elseif (!empty($panduanTableError) && $panduanTableError)
            <p class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                Panduan detail menampilkan informasi kategori yang tersedia. Jalankan migrasi PBI-5 untuk memuat panduan lengkap.
            </p>
        @endif

        <section class="grid gap-4 md:grid-cols-3" aria-label="Langkah awal memilah sampah">
            <article class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 font-bold text-white">1</span>
                <div><h2 class="font-bold">Pisahkan sejak awal</h2><p class="mt-1 text-sm leading-5 text-slate-500">Siapkan wadah terpisah untuk organik, plastik, dan kertas sebelum membuang.</p></div>
            </article>
            <article class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 font-bold text-white">2</span>
                <div><h2 class="font-bold">Bersihkan dan keringkan</h2><p class="mt-1 text-sm leading-5 text-slate-500">Kosongkan sisa makanan atau minuman agar sampah bisa didaur ulang.</p></div>
            </article>
            <article class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 font-bold text-white">3</span>
                <div><h2 class="font-bold">Buang sesuai jadwal</h2><p class="mt-1 text-sm leading-5 text-slate-500">Ikuti jadwal setor dan bawa ke titik yang menerima kategori tersebut.</p></div>
            </article>
        </section>

        <section class="mt-7" aria-labelledby="kategoriTitle">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div><h2 id="kategoriTitle" class="text-xl font-extrabold text-slate-900">Kategori Sampah</h2><p class="mt-1 text-sm text-slate-500">Pilih kategori untuk melihat instruksi pemilahan.</p></div>
                <span class="text-sm text-slate-500">{{ $kategoris->count() }} kategori</span>
            </div>

            @if (!empty($databaseError) && $databaseError)
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-600">Kategori belum dapat ditampilkan.</div>
            @elseif ($kategoris->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center" role="status">
                    <h3 class="font-bold text-slate-900">Belum ada kategori sampah aktif</h3>
                    <p class="mt-1 text-sm text-slate-500">Kategori akan tampil di sini setelah tersedia.</p>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" id="kategoriCards">
                    @foreach ($kategoris as $kategori)
                        @php
                            $colorName = mb_strtolower($kategori->nama_kategori);
                            $colorKey = $kategori->warna_label ?: (str_contains($colorName, 'plastik') ? 'bg-blue-500' : (str_contains($colorName, 'kertas') ? 'bg-amber-500' : 'bg-emerald-500'));
                            $color = match ($colorKey) {
                                'bg-blue-500' => ['bar' => 'bg-sky-600', 'badge' => 'bg-sky-600', 'soft' => 'bg-sky-50'],
                                'bg-amber-500' => ['bar' => 'bg-amber-500', 'badge' => 'bg-amber-500', 'soft' => 'bg-amber-50'],
                                'bg-orange-500' => ['bar' => 'bg-orange-500', 'badge' => 'bg-orange-500', 'soft' => 'bg-orange-50'],
                                'bg-purple-500' => ['bar' => 'bg-purple-600', 'badge' => 'bg-purple-600', 'soft' => 'bg-purple-50'],
                                'bg-rose-500' => ['bar' => 'bg-rose-600', 'badge' => 'bg-rose-600', 'soft' => 'bg-rose-50'],
                                default => ['bar' => 'bg-lime-600', 'badge' => 'bg-lime-600', 'soft' => 'bg-lime-50'],
                            };
                            $examples = collect(preg_split('/[,;\n]+/', $kategori->karakteristik ?? ''))->map(fn ($item) => trim($item))->filter()->take(4);
                            $searchText = mb_strtolower(implode(' ', [$kategori->nama_kategori, $kategori->kode, $kategori->deskripsi, $kategori->karakteristik, $kategori->cara_penanganan]));
                            $activeGuide = $panduanByKategori->get($kategori->id_kategori, collect())->first();
                        @endphp
                        <article data-guide-card data-search="{{ $searchText }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="h-1.5 {{ $color['bar'] }}"></div>
                            <div class="p-5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $color['badge'] }} text-sm font-extrabold text-white">{{ mb_strtoupper(mb_substr($kategori->nama_kategori, 0, 1)) }}</span>
                                    <div class="min-w-0"><h3 class="truncate font-extrabold text-slate-900">{{ $kategori->nama_kategori }}</h3><p class="text-xs text-slate-500">Kode {{ $kategori->kode }}</p></div>
                                </div>
                                <p class="mt-3 min-h-10 text-sm leading-5 text-slate-600">{{ $kategori->deskripsi ?: ($activeGuide?->ringkasan ?: 'Pelajari cara memilah kategori sampah ini.') }}</p>
                                @if ($examples->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-1.5" aria-label="Contoh sampah">
                                        @foreach ($examples as $example)
                                            <span class="rounded-full {{ $color['soft'] }} px-2.5 py-1 text-xs font-medium text-slate-700">{{ $example }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <a href="{{ route('panduan.kategori', $kategori->id_kategori) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-emerald-700 hover:text-emerald-900">
                                    Lihat detail kategori <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p id="searchEmpty" class="mt-4 hidden rounded-xl bg-white p-5 text-center text-sm text-slate-600" role="status">Tidak ada kategori yang cocok dengan pencarianmu.</p>
            @endif
        </section>

        <section class="mt-6 grid gap-4 xl:grid-cols-[1.7fr_0.8fr]" aria-label="Tips pemilahan">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-extrabold text-slate-900">Kesalahan yang sering terjadi</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    <li class="grid gap-2 py-3 sm:grid-cols-[1fr_auto_1.1fr] sm:items-center"><span class="flex items-center gap-2"><span class="text-rose-500">&#10005;</span> Gelas minuman masih berisi sisa</span><span class="text-slate-400" aria-hidden="true">&rarr;</span><span class="flex items-center gap-2 text-slate-500"><span class="text-emerald-600">&#10003;</span> Kosongkan dulu, lalu pilah sesuai bahan</span></li>
                    <li class="grid gap-2 py-3 sm:grid-cols-[1fr_auto_1.1fr] sm:items-center"><span class="flex items-center gap-2"><span class="text-rose-500">&#10005;</span> Bungkus berminyak dicampur kertas</span><span class="text-slate-400" aria-hidden="true">&rarr;</span><span class="flex items-center gap-2 text-slate-500"><span class="text-emerald-600">&#10003;</span> Pisahkan dari kertas yang dapat didaur ulang</span></li>
                    <li class="grid gap-2 py-3 sm:grid-cols-[1fr_auto_1.1fr] sm:items-center"><span class="flex items-center gap-2"><span class="text-rose-500">&#10005;</span> Sisa makanan dibuang bersama kemasannya</span><span class="text-slate-400" aria-hidden="true">&rarr;</span><span class="flex items-center gap-2 text-slate-500"><span class="text-emerald-600">&#10003;</span> Pisahkan sisa makanan dari kemasan</span></li>
                    <li class="grid gap-2 py-3 sm:grid-cols-[1fr_auto_1.1fr] sm:items-center"><span class="flex items-center gap-2"><span class="text-rose-500">&#10005;</span> Kantong berisi sampah campuran</span><span class="text-slate-400" aria-hidden="true">&rarr;</span><span class="flex items-center gap-2 text-slate-500"><span class="text-emerald-600">&#10003;</span> Buka dan pilah isinya terlebih dahulu</span></li>
                </ul>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-extrabold text-slate-900">Warna Tempat Sampah</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center gap-3"><span class="h-7 w-7 rounded-lg bg-lime-600"></span><p class="text-sm"><strong>Organik</strong><br><span class="text-xs text-slate-500">Tong berlabel hijau</span></p></div>
                    <div class="flex items-center gap-3"><span class="h-7 w-7 rounded-lg bg-sky-600"></span><p class="text-sm"><strong>Plastik</strong><br><span class="text-xs text-slate-500">Tong berlabel biru</span></p></div>
                    <div class="flex items-center gap-3"><span class="h-7 w-7 rounded-lg bg-amber-500"></span><p class="text-sm"><strong>Kertas</strong><br><span class="text-xs text-slate-500">Tong berlabel kuning</span></p></div>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">Periksa label pada tempat sampah di lokasi karena kode warna dapat berbeda.</p>
            </article>
        </section>
    </main>
</div>

<script>
function panduanPage() {
    return {
        search: '',
        filterCards() {
            const query = this.search.trim().toLocaleLowerCase('id');
            const cards = [...document.querySelectorAll('[data-guide-card]')];
            let visible = 0;

            cards.forEach((card) => {
                const matches = card.dataset.search.includes(query);
                card.style.display = matches ? '' : 'none';
                if (matches) visible++;
            });

            const emptyState = document.getElementById('searchEmpty');
            if (emptyState) emptyState.style.display = query && visible === 0 ? 'block' : 'none';
        },
    };
}
</script>
</body>
</html>
