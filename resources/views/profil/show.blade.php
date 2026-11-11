@extends('layouts.app')

@section('judul', 'Profil Civitas Kampus')
@section('isi')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                <span class="text-emerald-700 font-bold">Akun Pengguna</span>
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Profil Civitas Academica
            </h2>

            <p class="mt-1 text-xs sm:text-sm text-slate-500">
                Informasi identitas kampus dan data akun pengguna.
            </p>
        </div>

        <a
            href="{{ route('profil.edit') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                />
            </svg>

            <span>Edit Data & Pengaturan</span>
        </a>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Digital University ID Card -->
        <div class="lg:col-span-1 rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">

            <!-- Card Header -->
            <div class="bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 p-6 text-white text-center relative border-b border-emerald-500/30">

                <div class="absolute top-3 right-3">
                    <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-extrabold text-white border border-white/30 shadow-2xs">
                        {{ strtoupper($user->peran) }}
                    </span>
                </div>

                <!-- Foto Profil -->
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-2xl bg-white text-emerald-800 text-3xl font-black shadow-md border border-white/90 overflow-hidden mb-3">

                    @if ($user->foto_profil)
                        <img
                            src="{{ asset('storage/' . $user->foto_profil) }}"
                            class="h-full w-full object-cover"
                            alt="{{ $user->nama_lengkap }}"
                        >
                    @else
                        {{ strtoupper(substr($user->nama_lengkap, 0, 2)) }}
                    @endif

                </div>

                <h3 class="font-extrabold text-lg text-white tracking-tight drop-shadow-xs">
                    {{ $user->nama_lengkap }}
                </h3>

                <p class="text-xs text-emerald-100 font-medium mt-0.5">
                    {{ $user->program_studi ?: 'Civitas Telkom University' }}
                </p>

                <div class="mt-2 text-[11px] font-mono font-bold text-white bg-white/20 px-2.5 py-1 rounded-lg inline-block border border-white/25">
                    NIM/NIP: {{ $user->nim_nip }}
                </div>
            </div>

            <!-- Card Details -->
            <div class="p-6 space-y-4 text-xs">

                <!-- Email -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <span class="text-slate-400 font-semibold uppercase tracking-wider">
                        Email Kampus
                    </span>

                    <span class="font-bold text-slate-800">
                        {{ $user->email_kampus }}
                    </span>
                </div>

                <!-- No HP -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <span class="text-slate-400 font-semibold uppercase tracking-wider">
                        No. Handphone
                    </span>

                    <span class="font-medium text-slate-700">
                        {{ $user->no_hp ?? '-' }}
                    </span>
                </div>

                <!-- Status -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <span class="text-slate-400 font-semibold uppercase tracking-wider">
                        Status Akun
                    </span>

                    <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                        {{ ucfirst($user->status_akun) }}
                    </span>
                </div>

                <!-- Tanggal Bergabung -->
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-semibold uppercase tracking-wider">
                        Bergabung Sejak
                    </span>

                    <span class="font-medium text-slate-700">
                        {{ $user->tanggal_bergabung?->translatedFormat('d F Y') }}
                    </span>
                </div>

            </div>
        </div>

        <!-- Informasi Akun -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Data Akun -->
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-xs p-6">

                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">

                    <div>
                        <h3 class="font-bold text-sm text-slate-900">
                            Informasi Akun
                        </h3>

                        <p class="text-xs text-slate-400 mt-1">
                            Data identitas yang terdaftar pada sistem.
                        </p>
                    </div>

                    <span class="text-xs font-bold text-emerald-600">
                        Profil Pengguna
                    </span>

                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <!-- Nama Lengkap -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Nama Lengkap
                        </span>

                        <span class="text-sm font-semibold text-slate-800">
                            {{ $user->nama_lengkap }}
                        </span>
                    </div>

                    <!-- Nama Panggilan -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Nama Panggilan
                        </span>

                        <span class="text-sm font-semibold text-slate-800">
                            {{ $user->nama_panggilan ?: '-' }}
                        </span>
                    </div>

                    <!-- NIM/NIP -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            NIM / NIP
                        </span>

                        <span class="text-sm font-mono font-semibold text-slate-800">
                            {{ $user->nim_nip }}
                        </span>
                    </div>

                    <!-- Program Studi -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Program Studi / Unit Kerja
                        </span>

                        <span class="text-sm font-semibold text-slate-800">
                            {{ $user->program_studi ?: '-' }}
                        </span>
                    </div>

                    <!-- Email -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Email Kampus
                        </span>

                        <span class="text-sm font-semibold text-slate-800">
                            {{ $user->email_kampus }}
                        </span>
                    </div>

                    <!-- No HP -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            No. Handphone
                        </span>

                        <span class="text-sm font-semibold text-slate-800">
                            {{ $user->no_hp ?: '-' }}
                        </span>
                    </div>

                </div>
            </div>

            <!-- Status Akun -->
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-xs p-6">

                <h3 class="font-bold text-sm text-slate-900 mb-4">
                    Status Akun
                </h3>

                <div class="flex items-center justify-between rounded-2xl bg-emerald-50 border border-emerald-100 px-4 py-4">

                    <div class="flex items-center gap-3">

                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3c-2.755 0-5.292.737-7.5 2.016A11.955 11.955 0 004.5 12c0 2.755.737 5.292 2.016 7.5A11.955 11.955 0 0012 21c2.755 0 5.292-.737 7.5-2.016A11.955 11.955 0 0021 12c0-2.755-.737-5.292-2.016-7.5z"
                                />
                            </svg>
                        </span>

                        <div>
                            <p class="text-xs font-bold text-emerald-800">
                                Akun {{ ucfirst($user->status_akun) }}
                            </p>

                            <p class="text-[11px] text-emerald-600 mt-0.5">
                                Akun Anda terdaftar dalam sistem.
                            </p>
                        </div>

                    </div>

                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Aktif
                    </span>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection