<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $kategori?->nama_kategori ?? 'Detail kategori' }} - Panduan PilahKu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f4f8f5] text-slate-800">
    <header class="bg-[#0e422e] text-white">
        <nav class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-tight">PilahKu</a>
            <a href="{{ route('panduan.index') }}" class="text-sm font-semibold text-emerald-100 hover:text-white">Semua Panduan</a>
        </nav>
    </header>

    <main class="mx-auto max-w-4xl px-5 py-8 sm:px-8 sm:py-12">
        <a href="{{ route('panduan.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">&larr; Kembali ke panduan</a>

        @if (!empty($databaseError) && $databaseError)
            <section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-900" role="alert">
                <h1 class="font-bold">Detail panduan belum dapat dimuat</h1>
                <p class="mt-1 text-sm">Ada kendala koneksi ke database. Silakan coba beberapa saat lagi.</p>
            </section>
        @elseif (! $kategori)
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-8" role="status">
                <h1 class="text-2xl font-extrabold text-slate-900">Kategori tidak ditemukan</h1>
                <p class="mt-2 text-slate-600">Kategori ini tidak tersedia atau sudah tidak aktif.</p>
            </section>
        @else
            <article class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-emerald-700 px-7 py-8 text-white sm:px-10 sm:py-10">
                    <p class="text-sm font-bold uppercase tracking-widest text-emerald-100">Kategori sampah</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight">{{ $kategori->nama_kategori }}</h1>
                    @if ($kategori->kode)
                        <p class="mt-2 text-sm text-emerald-100">Kode {{ $kategori->kode }}</p>
                    @endif
                    @if ($kategori->deskripsi)
                        <p class="mt-4 max-w-2xl leading-7 text-emerald-50">{{ $kategori->deskripsi }}</p>
                    @endif
                </div>

                <div class="space-y-7 px-7 py-8 sm:px-10 sm:py-10">
                    @if ($kategori->karakteristik)
                        @php($examples = collect(preg_split('/[,;\n]+/', $kategori->karakteristik))->map(fn ($item) => trim($item))->filter())
                        <section>
                            <h2 class="font-extrabold text-slate-900">Contoh sampah</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($examples as $example)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-900">{{ $example }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($panduan->isNotEmpty())
                        @foreach ($panduan as $item)
                            <section class="border-t border-slate-100 pt-6">
                                <h2 class="text-lg font-extrabold text-slate-900">{{ $item->judul }}</h2>
                                @if ($item->ringkasan)
                                    <p class="mt-2 leading-7 text-slate-600">{{ $item->ringkasan }}</p>
                                @endif
                                <div class="mt-4 whitespace-pre-line leading-8 text-slate-700">{{ $item->langkah_pemilahan }}</div>
                            </section>
                        @endforeach
                    @elseif ($kategori->cara_penanganan)
                        <section>
                            <h2 class="text-lg font-extrabold text-slate-900">Cara memilah dan menangani</h2>
                            <div class="mt-3 whitespace-pre-line leading-8 text-slate-700">{{ $kategori->cara_penanganan }}</div>
                        </section>
                    @else
                        <section class="rounded-2xl bg-amber-50 p-5 text-amber-900" role="status">
                            <h2 class="font-bold">Instruksi detail belum tersedia</h2>
                            <p class="mt-1 text-sm">Informasi pemilahan untuk kategori ini belum ditambahkan.</p>
                        </section>
                    @endif

                    @if (!empty($panduanTableError) && $panduanTableError)
                        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">Detail ditampilkan dari informasi kategori. Migrasikan tabel panduan untuk memuat instruksi tambahan.</p>
                    @endif
                </div>
            </article>
        @endif
    </main>
</body>
</html>
