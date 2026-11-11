<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $panduan?->judul ?? 'Panduan tidak ditemukan' }} - PilahKu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f7faf7] text-slate-800">
    <header class="border-b border-emerald-100 bg-white">
        <nav class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-tight text-emerald-700">PilahKu</a>
            <a href="{{ route('panduan.index') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-700">Semua Panduan</a>
        </nav>
    </header>

    <main class="mx-auto max-w-4xl px-6 py-10 md:py-14">
        <a href="{{ route('panduan.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">&larr; Kembali ke daftar</a>

        @if ($databaseError)
            <section class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900" role="alert">
                <h1 class="font-bold">Detail panduan belum dapat dimuat</h1>
                <p class="mt-1 text-sm">Ada kendala saat menghubungkan ke data panduan. Silakan coba kembali beberapa saat lagi.</p>
                <a href="{{ url()->current() }}" class="mt-4 inline-block text-sm font-bold underline">Coba lagi</a>
            </section>
        @elseif (! $panduan)
            <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-8" role="status">
                <h1 class="text-2xl font-bold text-slate-900">Panduan tidak ditemukan</h1>
                <p class="mt-2 text-slate-600">Topik ini tidak tersedia atau sudah tidak aktif.</p>
            </section>
        @else
            <article class="mt-7 overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-sm">
                <div class="bg-emerald-700 px-7 py-8 text-white md:px-10 md:py-10">
                    <p class="text-sm font-bold uppercase tracking-widest text-emerald-100">{{ $panduan->kategori->nama_kategori }}</p>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight md:text-4xl">{{ $panduan->judul }}</h1>
                    @if ($panduan->ringkasan)
                        <p class="mt-4 max-w-2xl leading-7 text-emerald-50">{{ $panduan->ringkasan }}</p>
                    @endif
                </div>
                <section class="px-7 py-8 md:px-10 md:py-10" aria-labelledby="langkahTitle">
                    <h2 id="langkahTitle" class="text-xl font-bold text-slate-900">Langkah Pemilahan</h2>
                    <div class="mt-4 whitespace-pre-line text-base leading-8 text-slate-700">{{ $panduan->langkah_pemilahan }}</div>
                </section>
            </article>
        @endif
    </main>
</body>
</html>
