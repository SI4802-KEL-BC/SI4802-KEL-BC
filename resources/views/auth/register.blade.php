{{-- PBI-01: registrasi akun pengguna (User). --}}
@extends('layouts.app')

@section('judul', 'Daftar · PilahKu')
@section('polos', '1')
@section('lebar', 'max-w-2xl')

@section('isi')
    <div class="pk-kartu p-6 sm:p-10">
        <div class="flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-xl bg-pk-hijau text-base font-bold text-white">P</span>
            <span class="text-lg font-semibold tracking-tight">PilahKu</span>
        </div>

        <h1 class="mt-6 text-2xl font-bold tracking-tight">Buat akun baru</h1>
        <p class="mt-1.5 text-sm text-pk-teks-2">Isi data diri sesuai identitas kampus. Kolom bertanda * wajib diisi.</p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-xl bg-pk-merah-bg px-4 py-3 text-sm text-pk-merah">
                <p class="font-semibold">Registrasi belum berhasil:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('register.simpan') }}" novalidate class="mt-8 grid gap-5 sm:grid-cols-2">
            @csrf

            <div class="space-y-1.5 sm:col-span-2">
                <label for="nama_lengkap" class="pk-label">Nama Lengkap *</label>
                <input id="nama_lengkap" name="nama_lengkap" type="text" maxlength="100" value="{{ old('nama_lengkap') }}" autocomplete="name" class="pk-input h-11">
            </div>

            <div class="space-y-1.5">
                <label for="nama_panggilan" class="pk-label">Nama Panggilan</label>
                <input id="nama_panggilan" name="nama_panggilan" type="text" maxlength="50" value="{{ old('nama_panggilan') }}" class="pk-input h-11">
            </div>

            <div class="space-y-1.5">
                <label for="nim_nip" class="pk-label">NIM / NIP *</label>
                <input id="nim_nip" name="nim_nip" type="text" inputmode="numeric" maxlength="20" value="{{ old('nim_nip') }}" class="pk-input h-11">
            </div>

            <div class="space-y-1.5 sm:col-span-2">
                <label for="email_kampus" class="pk-label">Email Kampus *</label>
                <input id="email_kampus" name="email_kampus" type="email" maxlength="100" value="{{ old('email_kampus') }}" autocomplete="username" placeholder="nama@student.kampus.ac.id" class="pk-input h-11">
            </div>

            <div class="space-y-1.5">
                <label for="program_studi" class="pk-label">Program Studi</label>
                <input id="program_studi" name="program_studi" type="text" maxlength="100" value="{{ old('program_studi') }}" class="pk-input h-11">
            </div>

            <div class="space-y-1.5">
                <label for="no_hp" class="pk-label">No. HP</label>
                <input id="no_hp" name="no_hp" type="tel" maxlength="16" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx" class="pk-input h-11">
            </div>

            <div class="space-y-1.5">
                <label for="kata_sandi" class="pk-label">Kata Sandi *</label>
                <input id="kata_sandi" name="kata_sandi" type="password" autocomplete="new-password" placeholder="Min. 8 karakter" class="pk-input h-11">
            </div>

            <div class="space-y-1.5">
                <label for="kata_sandi_confirmation" class="pk-label">Ulangi Kata Sandi *</label>
                <input id="kata_sandi_confirmation" name="kata_sandi_confirmation" type="password" autocomplete="new-password" class="pk-input h-11">
            </div>

            <button type="submit" class="pk-tombol-primer w-full sm:col-span-2">Daftar</button>
        </form>

        <p class="mt-6 text-center text-sm text-pk-teks-2">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-pk-hijau-teks hover:underline">Masuk</a>
        </p>
    </div>
@endsection
