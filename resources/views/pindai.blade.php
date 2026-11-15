{{--
    PBI-13 · Pindai QR Tong Sampah
    Pengguna memindai QR yang tertempel di tong sampah (URL tetap /pindai/{kode_perangkat}); itu membuat sesi atas
    namanya di tong tersebut, lalu kamera tong memotret dan mengirim foto ke API.
    Variabel dari controller:
        $sessionTtl        int     batas waktu sesi menunggu foto (detik)
        $sesiAktif         ?SesiPembuangan  sesi Aktif milik pengguna (relasi: lokasi)
        $aktivitasTerbaru  Collection<AktivitasPemilahan>  maks. 3 aktivitas terakhir (relasi: kategori, lokasi)
    Aksi:
        GET  /pindai/{kode}   tujuan URL di QR tong
        POST /pindai          field: kode_perangkat (input manual)
--}}
@extends('layouts.app')

@section('judul', 'Pindai QR · PilahKu')

@section('breadcrumb')
    <span>Aktivitas</span>
    <span class="mx-2 text-[#B7C4BC]">/</span>
    <span class="font-semibold text-[#0F2A1C]">Pindai QR</span>
@endsection

@section('isi')
@php
    $sesiAktif = $sesiAktif ?? null;
    $aktivitasTerbaru = $aktivitasTerbaru ?? collect();
    $sisaDetik = $sesiAktif ? max(0, $sesiAktif->kedaluwarsa_pada->timestamp - now()->timestamp) : 0;
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Pindai QR Tong Sampah</h1>
        <p class="mt-1 text-sm text-[#5B6B62]">Pindai QR yang tertempel di tong sampah. Aktivitas pemilahanmu tercatat otomatis, tanpa isi formulir.</p>
    </div>
</div>

@if ($errors->any())
    <div role="alert" class="mt-6 flex items-start gap-3 rounded-2xl border border-[#FECACA] bg-[#FEF2F2] px-5 py-4">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#DC2626] text-white">@include('partials.ikon', ['n' => 'silang', 'c' => 'h-4 w-4'])</span>
        <div class="text-sm">
            <p class="font-semibold text-[#B91C1C]">QR belum bisa dipakai</p>
            <p class="mt-0.5 text-[#0F2A1C]">{{ $errors->first() }}</p>
        </div>
    </div>
@endif

{{-- Status sesi --}}
@if ($sesiAktif)
    <div class="mt-6 rounded-2xl border border-[#BBE5CA] bg-[#E8F7EE] px-5 py-4">
        <div class="flex items-start gap-3">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#16A34A] text-white">@include('partials.ikon', ['n' => 'centang', 'c' => 'h-4 w-4'])</span>
            <div class="min-w-0 flex-1 text-sm">
                <p class="font-semibold text-[#15803D]">QR berhasil dipindai di {{ $sesiAktif->lokasi->nama_lokasi ?? 'tempat sampah' }}</p>
                <p class="mt-0.5 text-[#0F2A1C]">Silakan buang sampahmu sekarang. Kamera tempat sampah akan memotret sampah secara otomatis sebelum
                    <strong>{{ $sesiAktif->kedaluwarsa_pada->format('H:i') }}</strong>.</p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/70">
                        <div id="bilah-sesi" class="h-full rounded-full bg-[#16A34A] transition-[width] duration-1000 ease-linear" style="width: {{ $sessionTtl > 0 ? min(100, round($sisaDetik / $sessionTtl * 100)) : 0 }}%"></div>
                    </div>
                    <span id="hitung-mundur" data-sisa-detik="{{ $sisaDetik }}" class="font-mono text-sm font-bold tabular-nums text-[#15803D]">{{ sprintf('%02d:%02d', intdiv($sisaDetik, 60), $sisaDetik % 60) }}</span>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-[#BBE5CA] bg-[#F1FAF4] px-5 py-4">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#16A34A] text-white">@include('partials.ikon', ['n' => 'info', 'c' => 'h-4 w-4'])</span>
        <div class="text-sm">
            <p class="font-semibold text-[#15803D]">Belum ada sesi aktif</p>
            <p class="mt-0.5 text-[#0F2A1C]">Dekati tong sampah yang sesuai dengan jenis sampahmu, lalu pindai QR yang tertempel di tong.</p>
        </div>
    </div>
@endif

<div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    {{-- Kartu pemindai --}}
    <section class="self-start rounded-2xl border border-[#E1EAE4] bg-white p-6 shadow-[0_1px_2px_rgba(15,42,28,.04)] sm:p-8">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Pindai QR Tong</h2>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F7EE] px-2.5 py-1 text-xs font-semibold text-[#15803D]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span> HP &amp; laptop
            </span>
        </div>

        {{-- Pemindai kamera (di dalam web) --}}
        <div class="mt-6">
            <div id="area-kamera" class="relative hidden overflow-hidden rounded-2xl bg-[#0F2A1C]">
                <video id="video-kamera" class="aspect-square w-full object-cover sm:aspect-video" playsinline muted></video>
                <span class="pointer-events-none absolute left-6 top-6 h-10 w-10 rounded-tl-2xl border-l-4 border-t-4 border-[#4ADE80]"></span>
                <span class="pointer-events-none absolute right-6 top-6 h-10 w-10 rounded-tr-2xl border-r-4 border-t-4 border-[#4ADE80]"></span>
                <span class="pointer-events-none absolute bottom-6 left-6 h-10 w-10 rounded-bl-2xl border-b-4 border-l-4 border-[#4ADE80]"></span>
                <span class="pointer-events-none absolute bottom-6 right-6 h-10 w-10 rounded-br-2xl border-b-4 border-r-4 border-[#4ADE80]"></span>
            </div>

            <div id="placeholder-kamera" class="flex flex-col items-center rounded-2xl border border-dashed border-[#D5E2DA] bg-[#F6FAF7] px-4 py-10 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-[#E8F7EE] text-[#16A34A]">@include('partials.ikon', ['n' => 'pindai', 'c' => 'h-7 w-7'])</span>
                <p class="mt-3 text-sm font-semibold">Arahkan kamera ke QR di tong sampah</p>
                <p class="mt-1 max-w-xs text-xs text-[#5B6B62]">Kamera hanya dipakai untuk membaca QR dan tidak menyimpan gambar.</p>
            </div>

            <p id="status-kamera" class="mt-3 hidden rounded-xl bg-[#F3F7F4] px-4 py-2.5 text-sm text-[#5B6B62]" role="status"></p>

            <button type="button" id="tombol-kamera"
                    class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#16A34A] text-sm font-semibold text-white transition hover:bg-[#15803D] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#16A34A]/30">
                @include('partials.ikon', ['n' => 'kamera', 'c' => 'h-4 w-4'])
                <span id="label-kamera">Nyalakan kamera &amp; pindai</span>
            </button>
        </div>

        {{-- Cadangan: kode tong manual --}}
        <div class="mt-8 border-t border-[#EEF3F0] pt-6">
            <h3 class="text-sm font-semibold">Atau masukkan kode tong</h3>
            <p class="mt-1 text-xs text-[#5B6B62]">Kode tercetak di bawah QR pada tong, misalnya <code class="rounded bg-[#F3F7F4] px-1">DEV-0001</code>.</p>
            <form method="POST" action="{{ route('pindai.simpan') }}" class="mt-3 flex flex-col gap-3 sm:flex-row">
                @csrf
                <label for="kode_perangkat" class="sr-only">Kode tong</label>
                <input id="kode_perangkat" name="kode_perangkat" type="text" value="{{ old('kode_perangkat') }}" maxlength="30" autocomplete="off" autocapitalize="characters"
                       placeholder="DEV-0001" required
                       class="h-11 flex-1 rounded-xl border border-[#D5E2DA] px-4 font-mono text-sm outline-none placeholder:text-[#9AAFA2] focus:border-[#16A34A] focus:ring-4 focus:ring-[#16A34A]/10">
                <button type="submit" class="h-11 rounded-xl border border-[#16A34A] px-6 text-sm font-semibold text-[#15803D] transition hover:bg-[#F1FAF4]">Mulai Sesi</button>
            </form>
        </div>

        <p class="mt-6 text-xs leading-relaxed text-[#5B6B62]">
            Setelah QR dipindai, kamu punya {{ intdiv($sessionTtl, 60) }} menit untuk membuang sampah. Satu tong hanya melayani satu pengguna pada satu waktu.
            Kamera HP bawaan juga bisa dipakai: pindai QR-nya, lalu browser akan membuka halaman ini.
        </p>
    </section>

    {{-- Kolom kanan --}}
    <div class="space-y-6">
        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <h2 class="text-base font-semibold">Cara Mencatat Aktivitas</h2>
            <ol class="mt-4 space-y-4">
                @foreach ([
                    ['pin', 'Dekati tong sampah', 'Pilih tong yang sesuai dengan jenis sampahmu.'],
                    ['pindai', 'Pindai QR di tong', 'Pakai kamera HP atau tombol pindai di halaman ini.'],
                    ['sampah', 'Buang sampahmu', 'Kamera tong memotret dan sistem memverifikasi secara otomatis.'],
                ] as $i => [$ikonLangkah, $judulLangkah, $teksLangkah])
                    <li class="flex gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#E8F7EE] text-[#15803D]">@include('partials.ikon', ['n' => $ikonLangkah, 'c' => 'h-[18px] w-[18px]'])</span>
                        <div>
                            <p class="text-sm font-semibold">{{ $i + 1 }}. {{ $judulLangkah }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-[#5B6B62]">{{ $teksLangkah }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <h2 class="text-base font-semibold">Ketentuan Poin</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex gap-2.5"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#16A34A]"></span><span>Sampah yang terdeteksi sesuai kategori tempat sampah langsung <strong>Terverifikasi</strong> dan mendapat poin.</span></li>
                <li class="flex gap-2.5"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#F59E0B]"></span><span>Jika hasil deteksi kurang yakin, aktivitas ditinjau Admin terlebih dahulu.</span></li>
                <li class="flex gap-2.5"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#DC2626]"></span><span>Kategori yang tidak sesuai akan <strong>Ditolak</strong> tanpa poin (tidak ada pengurangan poin).</span></li>
            </ul>
        </section>

        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <h2 class="text-base font-semibold">Aktivitas Terakhir</h2>
            @forelse ($aktivitasTerbaru as $item)
                <a href="{{ url('/aktivitas/' . $item->id_aktivitas) }}" class="-mx-2 mt-1 block rounded-xl px-2 py-2.5 transition first:mt-3 hover:bg-[#F3F7F4]">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-semibold">{{ $item->kategori->nama_kategori ?? 'Belum dikenali' }}</p>
                        @include('partials.badge-status', ['status' => $item->status_verifikasi])
                    </div>
                    <p class="mt-0.5 truncate text-xs text-[#5B6B62]">{{ $item->lokasi->nama_lokasi ?? '-' }} · {{ $item->tanggal_waktu->translatedFormat('d M, H:i') }}</p>
                </a>
            @empty
                <div class="mt-3 flex flex-col items-center rounded-xl border border-dashed border-[#D5E2DA] px-4 py-6 text-center">
                    <span class="text-[#9AAFA2]">@include('partials.ikon', ['n' => 'kotak-masuk', 'c' => 'h-6 w-6'])</span>
                    <p class="mt-2 text-sm font-medium">Belum ada aktivitas</p>
                    <p class="text-xs text-[#5B6B62]">Aktivitas pertamamu muncul di sini setelah foto sampah diterima.</p>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection

@push('skrip')
<script>
    // --- Hitung mundur sesi aktif ---
    (function () {
        const teks = document.getElementById('hitung-mundur');
        const bilah = document.getElementById('bilah-sesi');
        if (!teks) return;
        const total = {{ (int) $sessionTtl }};
        const batas = Date.now() + parseInt(teks.dataset.sisaDetik, 10) * 1000;
        const timer = setInterval(function () {
            const sisa = Math.max(0, Math.round((batas - Date.now()) / 1000));
            teks.textContent = String(Math.floor(sisa / 60)).padStart(2, '0') + ':' + String(sisa % 60).padStart(2, '0');
            bilah.style.width = (total > 0 ? Math.min(100, sisa / total * 100) : 0) + '%';
            const hampir = sisa <= 60;
            teks.classList.toggle('text-[#15803D]', !hampir);
            teks.classList.toggle('text-[#B45309]', hampir);
            bilah.classList.toggle('bg-[#16A34A]', !hampir);
            bilah.classList.toggle('bg-[#F59E0B]', hampir);
            if (sisa === 0) clearInterval(timer);
        }, 1000);
    })();

    // --- Pemindai QR kamera (HP & laptop) ---
    (function () {
        const tombol = document.getElementById('tombol-kamera');
        const label = document.getElementById('label-kamera');
        const area = document.getElementById('area-kamera');
        const video = document.getElementById('video-kamera');
        const placeholder = document.getElementById('placeholder-kamera');
        const status = document.getElementById('status-kamera');
        const dasar = @json(url('/pindai'));
        let stream = null, pewaktu = null;

        const info = (teks, galat) => {
            status.textContent = teks;
            status.classList.remove('hidden');
            status.classList.toggle('bg-[#FEF2F2]', !!galat);
            status.classList.toggle('text-[#B91C1C]', !!galat);
            status.classList.toggle('bg-[#F3F7F4]', !galat);
            status.classList.toggle('text-[#5B6B62]', !galat);
        };
        const berhenti = () => {
            clearInterval(pewaktu); pewaktu = null;
            if (stream) stream.getTracks().forEach((t) => t.stop());
            stream = null; video.srcObject = null;
            area.classList.add('hidden'); placeholder.classList.remove('hidden');
            label.textContent = 'Nyalakan kamera & pindai';
        };
        // Hanya kode tong yang diterima: alamat tujuan selalu dibangun dari situs ini, bukan dari isi QR.
        const ambilKode = (isi) => {
            try {
                const m = new URL(isi).pathname.match(/\/pindai\/([A-Za-z0-9_-]+)\/?$/);
                if (m) return m[1];
            } catch (e) { /* bukan URL */ }
            return /^[A-Za-z0-9_-]{1,30}$/.test(isi) ? isi : null;
        };

        // Pembaca QR: BarcodeDetector bawaan browser bila ada (Chrome Android/macOS); bila tidak (mis. Chrome di
        // Windows/Linux), pakai jsQR yang dimuat hanya saat dibutuhkan.
        const muatJsQR = () => new Promise((selesai, gagal) => {
            if (window.jsQR) return selesai();
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
            s.onload = selesai;
            s.onerror = () => gagal(new Error('jsQR tidak bisa dimuat'));
            document.head.appendChild(s);
        });
        async function siapkanPembaca() {
            if ('BarcodeDetector' in window) {
                const detektor = new BarcodeDetector({ formats: ['qr_code'] });
                return async () => { const hasil = await detektor.detect(video); return hasil.length ? hasil[0].rawValue : null; };
            }
            await muatJsQR();
            const kanvas = document.createElement('canvas');
            const ctx = kanvas.getContext('2d', { willReadFrequently: true });
            return async () => {
                if (!video.videoWidth) return null;
                const lebar = Math.min(640, video.videoWidth);
                const tinggi = Math.round(video.videoHeight * lebar / video.videoWidth);
                kanvas.width = lebar; kanvas.height = tinggi;
                ctx.drawImage(video, 0, 0, lebar, tinggi);
                const hasil = window.jsQR(ctx.getImageData(0, 0, lebar, tinggi).data, lebar, tinggi, { inversionAttempts: 'dontInvert' });
                return hasil ? hasil.data : null;
            };
        }

        tombol.addEventListener('click', async function () {
            if (stream) { berhenti(); status.classList.add('hidden'); return; }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                info('Kamera hanya bisa dipakai lewat HTTPS atau localhost. Masukkan kode tong di bawah.', true);
                return;
            }
            let baca;
            try {
                info('Menyiapkan pembaca QR…');
                baca = await siapkanPembaca();
            } catch (e) {
                info('Pembaca QR tidak bisa dimuat (butuh koneksi internet). Pakai kamera HP bawaan, atau masukkan kode tong di bawah.', true);
                return;
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            } catch (e) {
                info('Kamera tidak bisa diakses. Izinkan akses kamera, atau masukkan kode tong di bawah.', true);
                return;
            }
            video.srcObject = stream; await video.play();
            area.classList.remove('hidden'); placeholder.classList.add('hidden');
            label.textContent = 'Matikan kamera';
            info('Mencari QR…');
            let sibuk = false;
            pewaktu = setInterval(async function () {
                if (sibuk) return; // jangan menumpuk pembacaan bila satu frame lambat
                sibuk = true;
                try {
                    const isi = await baca();
                    if (!isi) return;
                    const kode = ambilKode(isi);
                    if (!kode) { info('QR ini bukan QR tong PilahKu.', true); return; }
                    berhenti();
                    info('QR terbaca, membuka sesi…');
                    window.location.href = dasar + '/' + encodeURIComponent(kode);
                } catch (e) { /* abaikan satu frame yang gagal */ } finally { sibuk = false; }
            }, 300);
        });
        window.addEventListener('pagehide', berhenti);
    })();
</script>
@endpush
