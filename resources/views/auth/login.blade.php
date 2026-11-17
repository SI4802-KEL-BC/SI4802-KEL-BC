{{-- Halaman masuk: form dikirim ke AuthController@masuk (tabel pengguna). --}}
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

            @if (session('status'))
                <p role="status" class="mt-6 rounded-xl bg-pk-kuning-bg px-3.5 py-2.5 text-sm font-medium text-pk-kuning">{{ session('status') }}</p>
            @endif

            <form id="form-login" method="post" action="{{ route('login.masuk') }}" novalidate class="mt-8 space-y-5">
                @csrf
                <div class="space-y-1.5">
                    <label for="email" class="pk-label">Email Kampus</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" autofocus placeholder="nama@student.kampus.ac.id" class="pk-input h-11">
                    @error('email')
                        <p role="alert" class="text-sm font-medium text-pk-merah">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="kata_sandi" class="pk-label">Kata Sandi</label>
                        <a href="#" class="text-xs font-medium text-pk-hijau-teks hover:underline">Lupa kata sandi?</a>
                    </div>
                    <input id="kata_sandi" name="kata_sandi" type="password" autocomplete="current-password" placeholder="••••••••" class="pk-input h-11">
                </div>

                @error('kata_sandi')
                    <p role="alert" class="text-sm font-medium text-pk-merah">{{ $message }}</p>
                @enderror

                <button type="submit" class="pk-tombol-primer w-full">Masuk</button>
            </form>

            <p class="mt-6 text-center text-sm text-pk-teks-2">
                Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-pk-hijau-teks hover:underline">Daftar</a>
            </p>

        </div>
    </div>
@endsection
