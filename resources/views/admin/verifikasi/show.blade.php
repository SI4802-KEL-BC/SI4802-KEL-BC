{{--
    PBI-13 · Panel Admin: Detail Verifikasi (tahap 2)
    Variabel dari controller:
        $aktivitas  AktivitasPemilahan dengan relasi: pengguna, kategori, lokasi, verifikasi
        $tahap1     ?VerifikasiSampah  hasil verifikasi otomatis (tahap 1)
    Aksi:
        POST /admin/verifikasi/{id}   field: keputusan = setujui|tolak, alasan (wajib bila tolak)
--}}
@extends('layouts.app')

@php
    $kode = 'AKT-' . str_pad($aktivitas->id_aktivitas, 4, '0', STR_PAD_LEFT);
    $skor = $tahap1?->skor_keyakinan !== null ? (float) $tahap1->skor_keyakinan : null;
    $ambang = (float) config('pilahku.ambang_skor', 0.7);
@endphp

@section('judul', 'Tinjau ' . $kode . ' · PilahKu')

@section('breadcrumb')
    <span>Admin</span>
    <span class="mx-2 text-[#B7C4BC]">/</span>
    <a href="{{ route('admin.verifikasi.index') }}" class="hover:text-[#0F2A1C]">Verifikasi Manual</a>
    <span class="mx-2 text-[#B7C4BC]">/</span>
    <span class="font-semibold text-[#0F2A1C]">Tinjau {{ $kode }}</span>
@endsection

@section('isi')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Tinjau Aktivitas {{ $kode }}</h1>
            @include('partials.badge-status', ['status' => $aktivitas->status_verifikasi])
        </div>
        <p class="mt-1 text-sm text-[#5B6B62]">Diajukan oleh {{ $aktivitas->pengguna->nama_lengkap ?? '-' }} pada {{ $aktivitas->tanggal_waktu->translatedFormat('d F Y, H:i') }}</p>
    </div>
    <a href="{{ route('admin.verifikasi.index') }}"
       class="inline-flex h-11 items-center gap-2 rounded-xl border border-[#D5E2DA] bg-white px-5 text-sm font-semibold hover:bg-[#F3F7F4]">
        ← Kembali ke antrean
    </a>
</div>

@if ($errors->any())
    <div role="alert" class="mt-6 rounded-2xl border border-[#FECACA] bg-[#FEF2F2] px-5 py-3 text-sm text-[#B91C1C]">{{ $errors->first() }}</div>
@endif

<div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    {{-- Foto + info --}}
    <section class="self-start rounded-2xl border border-[#E1EAE4] bg-white p-6 shadow-[0_1px_2px_rgba(15,42,28,.04)] sm:p-8">
        <h2 class="text-lg font-semibold">Foto Sampah</h2>
        @if ($aktivitas->foto_bukti)
            <img src="{{ asset('storage/' . $aktivitas->foto_bukti) }}" alt="Foto {{ $kode }}"
                 class="mt-4 aspect-[4/3] w-full rounded-xl border border-[#E1EAE4] bg-[#E8F2EC] object-cover">
        @else
            <div class="mt-4 flex aspect-[4/3] w-full items-center justify-center rounded-xl bg-[#E8F2EC] text-[#5B8A6C]">@include('partials.ikon', ['n' => 'kamera', 'c' => 'h-10 w-10'])</div>
        @endif

        <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-5 border-t border-[#EEF3F0] pt-5 text-sm">
            <div>
                <dt class="text-xs text-[#5B6B62]">Pengguna</dt>
                <dd class="mt-0.5 font-medium">{{ $aktivitas->pengguna->nama_lengkap ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-[#5B6B62]">Lokasi</dt>
                <dd class="mt-0.5 font-medium">{{ $aktivitas->lokasi->nama_lokasi ?? '-' }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-xs text-[#5B6B62]">Catatan pengguna</dt>
                <dd class="mt-0.5 leading-relaxed">{{ $aktivitas->catatan ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <div class="space-y-6">
        {{-- Hasil identifikasi otomatis --}}
        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Hasil Identifikasi</h2>
                <span class="rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-semibold text-[#92400E]">Tahap 1: {{ $tahap1?->hasil ?? '—' }}</span>
            </div>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-[#5B6B62]">Label terdeteksi</dt>
                    <dd class="rounded-md bg-[#F3F7F4] px-2 py-0.5 font-mono text-xs font-semibold ring-1 ring-[#E1EAE4]" data-label>{{ $tahap1?->label_terdeteksi ?? '—' }}</dd>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-[#5B6B62]">Skor keyakinan</dt>
                        <dd class="font-mono text-sm font-semibold tabular-nums" data-skor>{{ $skor !== null ? number_format($skor, 2) : '—' }}</dd>
                    </div>
                    <div class="relative mt-2 h-2 rounded-full bg-[#E1EAE4]" role="meter" aria-label="Skor keyakinan"
                         aria-valuemin="0" aria-valuemax="1" aria-valuenow="{{ $skor ?? 0 }}">
                        <span class="absolute inset-y-0 left-0 rounded-full {{ $skor !== null && $skor >= $ambang ? 'bg-[#16A34A]' : 'bg-[#F59E0B]' }}"
                              style="width: {{ $skor !== null ? round(min(1, max(0, $skor)) * 100) : 0 }}%"></span>
                        <span class="absolute -inset-y-1 w-0.5 rounded bg-[#0F2A1C]/50" style="left: {{ round($ambang * 100) }}%"></span>
                    </div>
                    <p class="mt-1.5 text-xs text-[#5B6B62]">Garis penanda = ambang {{ number_format($ambang, 2) }}</p>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-[#5B6B62]">Kategori</dt>
                    <dd class="font-semibold">{{ $aktivitas->kategori?->nama_kategori ?? 'Belum teridentifikasi' }}</dd>
                </div>
            </dl>
        </section>

        {{-- Keputusan --}}
        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <h2 class="text-base font-semibold">Keputusan Petugas</h2>
            <p class="mt-1 text-sm text-[#5B6B62]">Setujui untuk memberi poin, atau tolak dengan alasan.</p>

            <form method="POST" action="{{ route('admin.verifikasi.proses', $aktivitas) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="alasan" class="block text-sm font-semibold">Alasan <span class="font-normal text-[#5B6B62]">(wajib jika menolak)</span></label>
                    <textarea id="alasan" name="alasan" rows="3" maxlength="255" placeholder="Contoh: foto tidak jelas"
                              class="mt-2 w-full resize-none rounded-xl border border-[#D5E2DA] px-4 py-3 text-sm outline-none placeholder:text-[#9AAFA2] focus:border-[#16A34A] focus:ring-4 focus:ring-[#16A34A]/10">{{ old('alasan') }}</textarea>
                    @error('alasan')
                        <p class="mt-1.5 text-xs font-medium text-[#B91C1C]" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button type="submit" name="keputusan" value="tolak"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#DC2626] text-sm font-semibold text-white hover:bg-[#B91C1C]">
                        @include('partials.ikon', ['n' => 'silang', 'c' => 'h-4 w-4']) Tolak
                    </button>
                    <button type="submit" name="keputusan" value="setujui"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#16A34A] text-sm font-semibold text-white hover:bg-[#15803D]">
                        @include('partials.ikon', ['n' => 'centang', 'c' => 'h-4 w-4']) Setujui
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
