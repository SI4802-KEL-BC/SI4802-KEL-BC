@extends('layouts.app', ['title' => 'Pengaturan Profil'])

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                <a href="{{ route('profil.show') }}" class="hover:text-slate-600 transition">&larr; Kembali ke Profil</a>
                <span>&bull;</span>
                <span class="text-emerald-700 font-bold">Edit Akun</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Pengaturan Profil & Keamanan
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-slate-500">
                Kelola data diri, ganti kata sandi akun, atau kelola status keanggotaan civitas Anda.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Form Data Diri (2 cols) -->
        <form 
            method="POST" 
            action="{{ route('profil.update') }}" 
            enctype="multipart/form-data" 
            class="lg:col-span-2 space-y-5 rounded-3xl bg-white p-6 sm:p-8 border border-slate-200/80 shadow-xs"
        >
            @csrf 
            @method('PUT')

            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100 flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Biodata Civitas
            </h3>

            <!-- Foto Profil Upload -->
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-100 font-bold text-slate-600 border border-slate-200 overflow-hidden text-lg">
                    @if ($user->foto_profil)
                        <img src="{{ asset('storage/' . $user->foto_profil) }}" class="h-full w-full object-cover" alt="">
                    @else
                        {{ strtoupper(substr($user->nama_lengkap, 0, 2)) }}
                    @endif
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Foto Profil
                    </label>
                    <input 
                        type="file" 
                        name="foto_profil" 
                        accept="image/png,image/jpeg"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                    >
                    <span class="text-[11px] text-slate-400">JPG atau PNG, maksimal 2 MB</span>
                </div>
            </div>

            <!-- Nama Lengkap & Nama Panggilan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input 
                        name="nama_lengkap" 
                        value="{{ old('nama_lengkap', $user->nama_lengkap) }}" 
                        required 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Panggilan
                    </label>
                    <input 
                        name="nama_panggilan" 
                        value="{{ old('nama_panggilan', $user->nama_panggilan) }}" 
                        placeholder="mis. Nadia"
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                    >
                </div>
            </div>

            <!-- NIM & Email (Readonly) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        NIM / NIP <span class="text-[10px] text-slate-400 font-normal">(Terkunci Admin)</span>
                    </label>
                    <input 
                        value="{{ $user->nim_nip }}" 
                        disabled 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-500 font-mono cursor-not-allowed"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Email Kampus <span class="text-[10px] text-slate-400 font-normal">(Terkunci Admin)</span>
                    </label>
                    <input 
                        value="{{ $user->email_kampus }}" 
                        disabled 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-500 cursor-not-allowed"
                    >
                </div>
            </div>

            <!-- Prodi & HP -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Program Studi / Unit Kerja
                    </label>
                    <input 
                        name="program_studi" 
                        value="{{ old('program_studi', $user->program_studi) }}" 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        No. Handphone (WhatsApp)
                    </label>
                    <input 
                        name="no_hp" 
                        value="{{ old('no_hp', $user->no_hp) }}" 
                        placeholder="mis. 081234567890"
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                    >
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('profil.show') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        <!-- Sidebar Security & Danger Zone (1 col) -->
        <div class="space-y-6">
            <!-- Ubah Password Card -->
            <form 
                method="POST" 
                action="{{ route('profil.password') }}" 
                class="space-y-4 rounded-3xl bg-white p-6 border border-slate-200/80 shadow-xs"
            >
                @csrf 
                @method('PUT')
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Ubah Kata Sandi
                </h3>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Kata Sandi Saat Ini</label>
                    <input 
                        type="password" 
                        name="password_lama" 
                        placeholder="••••••••" 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-800 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Kata Sandi Baru</label>
                    <input 
                        type="password" 
                        name="kata_sandi" 
                        placeholder="Min. 8 karakter" 
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-800 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    >
                </div>

                <button type="submit" class="w-full rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 py-2.5 text-xs font-bold text-emerald-800 transition">
                    Perbarui Kata Sandi
                </button>
            </form>

            <!-- Deactivate Account Card -->
            <form 
                method="POST" 
                action="{{ route('profil.deactivate') }}" 
                class="space-y-3 rounded-3xl border border-rose-200 bg-rose-50/50 p-6" 
                onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan akun? Anda harus menghubungi admin kampus untuk mengaktifkannya kembali.')"
            >
                @csrf
                <div class="flex items-center gap-2 text-rose-700 font-bold text-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Nonaktifkan Akun
                </div>
                <p class="text-xs text-rose-800 leading-relaxed">
                    Riwayat aktivitas pemilahan dan saldo poin Anda akan tetap tersimpan aman di database kampus.
                </p>
                <input 
                    name="konfirmasi" 
                    placeholder="Ketik: NONAKTIFKAN" 
                    class="block w-full rounded-xl border border-rose-300 bg-white px-3 py-2 text-xs text-rose-900 placeholder-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                >
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Masukkan kata sandi akun" 
                    class="block w-full rounded-xl border border-rose-300 bg-white px-3 py-2 text-xs text-rose-900 placeholder-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                >
                <button type="submit" class="w-full rounded-xl border border-rose-300 bg-white py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 transition shadow-2xs">
                    Konfirmasi Nonaktifkan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
