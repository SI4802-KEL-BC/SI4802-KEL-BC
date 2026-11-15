<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PilahKu - Pilah sampah dengan benar</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f7faf7] text-slate-800">
    <header class="border-b border-emerald-100 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-tight text-emerald-700">PilahKu</a>
            <a href="{{ route('panduan.index') }}" class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Panduan Pemilahan</a>
        </nav>
    </header>

    <main class="mx-auto grid max-w-6xl gap-10 px-6 py-16 md:grid-cols-2 md:items-center md:py-24">
        <section>
            <p class="mb-4 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800">Mulai dari rumah</p>
            <h1 class="max-w-xl text-4xl font-extrabold leading-tight tracking-tight text-slate-900 md:text-5xl">Pilah sampah dengan cara yang tepat.</h1>
            <p class="mt-5 max-w-lg text-lg leading-8 text-slate-600">Temukan panduan praktis untuk mengenali kategori sampah dan menyiapkannya sebelum disetorkan atau dibuang.</p>
            <a href="{{ route('panduan.index') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white shadow-sm hover:bg-emerald-700">
                Lihat Panduan <span aria-hidden="true">&rarr;</span>
            </a>
        </section>
        <aside class="rounded-3xl border border-emerald-100 bg-white p-8 shadow-sm md:p-10">
            <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-2xl" aria-hidden="true">♻</div>
            <h2 class="text-xl font-bold text-slate-900">Sampah terpilah lebih bermanfaat</h2>
            <p class="mt-3 leading-7 text-slate-600">Pilih topik panduan untuk membaca langkah pemilahan sesuai kategori sampah.</p>
        </aside>
    </main>
</body>
</html>
