{{-- STUB PBI-13 - diganti saat PBI-01 masuk. Tampilan saja: form TIDAK memproses login. --}}
@extends('layouts.app')

@section('judul', 'Masuk · PilahKu')
@section('polos', '1')
@section('lebar', 'max-w-4xl')

@section('isi')
    <div class="pk-kartu grid overflow-hidden md:grid-cols-2">
        {{-- Panel merek (desktop) --}}
        <div class="relative hidden flex-col justify-between overflow-hidden bg-pk-tua p-10 text-white md:flex">
            <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full bg-pk-hijau/25"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 -left-10 size-56 rounded-full bg-white/5"></div>

            <div class="relative flex items-center gap-2.5">
                <span class="grid size-10 place-items-center rounded-xl bg-pk-hijau text-lg font-bold">P</span>
                <span class="text-xl font-semibold tracking-tight">PilahKu</span>
            </div>

            <div class="relative">
                <p class="text-2xl font-semibold leading-snug">Pilah sampahmu,<br>kumpulkan poinnya.</p>
                <ul class="mt-6 space-y-3 text-sm text-white/80">
                    @foreach (['Pindai QR di tempat sampah kampus', 'Aktivitas tercatat otomatis', 'Poin masuk setelah divalidasi'] as $poin)
                        <li class="flex items-center gap-3">
                            <span class="grid size-5 shrink-0 place-items-center rounded-full bg-pk-hijau/30 text-pk-muda">
                                <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                            </span>{{ $poin }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/50">Kampus Berkelanjutan</p>
        </div>

        {{-- Form --}}
        <div class="p-6 sm:p-10">
            <div class="flex items-center gap-2.5 md:hidden">
                <span class="grid size-9 place-items-center rounded-xl bg-pk-hijau text-base font-bold text-white">P</span>
                <span class="text-lg font-semibold tracking-tight">PilahKu</span>
            </div>

            <h1 class="mt-6 text-2xl font-bold tracking-tight md:mt-0">Masuk ke akunmu</h1>
            <p class="mt-1.5 text-sm text-pk-teks-2">Gunakan email kampus untuk melanjutkan.</p>

            {{-- Sengaja tanpa route POST: tidak ada pemrosesan login (PBI-01 milik Dea). --}}
            <form id="form-login" method="post" action="#" novalidate class="mt-8 space-y-5">
                <div class="space-y-1.5">
                    <label for="email" class="pk-label">Email Kampus</label>
                    <input id="email" name="email" type="email" autocomplete="username" placeholder="nama@student.kampus.ac.id" class="pk-input h-11">
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="kata_sandi" class="pk-label">Kata Sandi</label>
                        <a href="#" class="text-xs font-medium text-pk-hijau-teks hover:underline">Lupa kata sandi?</a>
                    </div>
                    <input id="kata_sandi" name="kata_sandi" type="password" autocomplete="current-password" placeholder="••••••••" class="pk-input h-11">
                </div>

                <p id="pesan-login" role="status" hidden
                   class="flex items-center gap-2 rounded-xl bg-pk-kuning-bg px-3.5 py-2.5 text-sm font-medium text-pk-kuning">
                    <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd"/></svg>
                    Login belum tersedia (PBI-01)
                </p>

                <button type="submit" class="pk-tombol-primer w-full">Masuk</button>
            </form>

            <p class="mt-6 text-center text-sm text-pk-teks-2">
                Belum punya akun? <a href="#" class="font-semibold text-pk-hijau-teks hover:underline">Daftar</a>
            </p>

            {{-- Jalan pintas lokal: hanya tampil bila routes/dev.php termuat (APP_ENV=local). --}}
            @if (app()->environment('local') && Route::has('dev-login'))
                <div class="mt-8 rounded-xl border border-dashed border-pk-garis bg-pk-latar p-4">
                    <p class="text-center text-xs font-semibold uppercase tracking-wider text-pk-teks-2">Khusus lokal</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('dev-login', 'user@pilahku.test') }}" class="pk-tombol-sekunder h-9 px-3 text-xs">Masuk sebagai User (dev)</a>
                        <a href="{{ route('dev-login', 'admin@pilahku.test') }}" class="pk-tombol-sekunder h-9 px-3 text-xs">Masuk sebagai Admin (dev)</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('form-login').addEventListener('submit', function (e) {
            e.preventDefault(); // tidak ada pengiriman data apa pun
            document.getElementById('pesan-login').hidden = false;
        });
    </script>
@endsection
