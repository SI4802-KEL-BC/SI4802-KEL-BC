{{--
    PBI-14 · Riwayat Aktivitas Pemilahan (GET /aktivitas)
    Variabel dari controller:
        $aktivitas       LengthAwarePaginator<AktivitasPemilahan> (relasi: kategori, lokasi, transaksiPoin), 10 per halaman
        $ringkasan       ['total','terverifikasi','menunggu','ditolak'] (milik pengguna, tanpa filter)
        $filter          filter tervalidasi: dari, sampai, kategori, status
        $adaFilter       bool, ada filter aktif
        $daftarKategori  kategori untuk dropdown; $daftarStatus = tiga status verifikasi
--}}
@extends('layouts.app')

@section('judul', 'Aktivitas Saya · PilahKu')
@section('halaman', 'Aktivitas Saya')

@section('isi')
@php
    $nilai = fn (string $k) => old($k, $filter[$k] ?? '');
    $kartu = [
        ['label' => 'Total Aktivitas', 'angka' => $ringkasan['total'], 'ikon' => 'daftar', 'chip' => 'bg-[#F1F5F3] text-[#0F3D26]'],
        ['label' => 'Terverifikasi', 'angka' => $ringkasan['terverifikasi'], 'ikon' => 'centang', 'chip' => 'bg-[#E8F7EE] text-[#16A34A]'],
        ['label' => 'Menunggu Validasi', 'angka' => $ringkasan['menunggu'], 'ikon' => 'jam', 'chip' => 'bg-[#FEF3C7] text-[#D97706]'],
        ['label' => 'Ditolak', 'angka' => $ringkasan['ditolak'], 'ikon' => 'silang', 'chip' => 'bg-[#FEE2E2] text-[#DC2626]'],
    ];
    $kodeAkt = fn ($a) => 'AKT-' . str_pad($a->id_aktivitas, 4, '0', STR_PAD_LEFT);
    $poinTeks = fn ($a) => match ($a->status_verifikasi) {
        'Terverifikasi' => '+' . (int) ($a->transaksiPoin?->jumlah_poin ?? 0),
        'Ditolak' => '0',
        default => '–',
    };
    $inputKelas = 'h-10 w-full rounded-lg border border-[#DCE5E0] bg-white px-3 text-sm text-[#0F2A1C] shadow-sm outline-none transition focus:border-[#16A34A] focus:ring-4 focus:ring-[#16A34A]/10';
    $tombolPrimer = 'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#16A34A] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#15803D] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#16A34A]/25';
    $tombolSekunder = 'inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-[#DCE5E0] bg-white px-4 text-sm font-semibold text-[#0F2A1C] shadow-sm transition hover:bg-[#F5F7F6]';
@endphp

{{-- Kepala halaman --}}
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold tracking-wide text-[#16A34A] uppercase">Riwayat</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-[#0B2E1D] sm:text-[28px]">Aktivitas Saya</h1>
        <p class="mt-1 text-sm text-[#6B7A72]">Riwayat aktivitas pemilahan sampahmu, dari yang terbaru.</p>
    </div>
    <a href="{{ url('/pindai') }}" class="{{ $tombolPrimer }}">
        @include('partials.ikon', ['n' => 'pindai', 'c' => 'h-4 w-4'])
        Pindai QR
    </a>
</div>

{{-- Ringkasan --}}
<section class="mt-7 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" aria-label="Ringkasan aktivitas">
    @foreach ($kartu as $k)
        <div class="rounded-xl border border-[#E4EBE7] bg-white p-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)] sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[13px] font-medium text-[#6B7A72]">{{ $k['label'] }}</p>
                <span class="grid h-8 w-8 place-items-center rounded-lg {{ $k['chip'] }}">@include('partials.ikon', ['n' => $k['ikon'], 'c' => 'h-4 w-4'])</span>
            </div>
            <p class="mt-3 text-[28px] leading-none font-semibold tracking-tight text-[#0B2E1D] tabular-nums">{{ $k['angka'] }}</p>
        </div>
    @endforeach
</section>

{{-- Pesan validasi filter --}}
@if ($errors->any())
    <div role="alert" class="mt-6 flex gap-3 rounded-xl border border-[#FECACA] bg-[#FEF2F2] px-4 py-3 text-sm text-[#B91C1C]">
        @include('partials.ikon', ['n' => 'peringatan', 'c' => 'mt-0.5 h-4 w-4 shrink-0'])
        <ul class="space-y-0.5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Daftar + filter dalam satu kartu --}}
<section class="mt-6 overflow-hidden rounded-xl border border-[#E4EBE7] bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
    <form method="GET" action="{{ route('aktivitas.index') }}" class="border-b border-[#EEF2F0] bg-[#FAFCFB] p-4 sm:p-5" aria-label="Filter riwayat">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-[#4B5B53]">Dari tanggal</span>
                <input type="date" name="dari" value="{{ $nilai('dari') }}" class="{{ $inputKelas }}">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-[#4B5B53]">Sampai tanggal</span>
                <input type="date" name="sampai" value="{{ $nilai('sampai') }}" class="{{ $inputKelas }}">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-[#4B5B53]">Kategori</span>
                <select name="kategori" class="{{ $inputKelas }}">
                    <option value="">Semua kategori</option>
                    @foreach ($daftarKategori as $kat)
                        <option value="{{ $kat->id_kategori }}" @selected((string) $nilai('kategori') === (string) $kat->id_kategori)>{{ $kat->nama_kategori }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-[#4B5B53]">Status</span>
                <select name="status" class="{{ $inputKelas }}">
                    <option value="">Semua status</option>
                    @foreach ($daftarStatus as $st)
                        <option value="{{ $st }}" @selected($nilai('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="{{ $tombolPrimer }} flex-1 lg:flex-none">Terapkan</button>
                @if ($adaFilter || $errors->any())
                    <a href="{{ route('aktivitas.index') }}" class="{{ $tombolSekunder }}">Reset filter</a>
                @endif
            </div>
        </div>
    </form>

    @if ($aktivitas->isEmpty())
        <div class="px-6 py-16 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-[#E8F7EE] text-[#16A34A] ring-8 ring-[#F3FAF6]">
                @include('partials.ikon', ['n' => $adaFilter ? 'daftar' : 'kotak-masuk', 'c' => 'h-6 w-6'])
            </div>
            @if ($adaFilter)
                <h2 class="mt-5 text-base font-semibold text-[#0B2E1D]">Tidak ada aktivitas sesuai filter</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-[#6B7A72]">Coba ubah rentang tanggal, kategori, atau status.</p>
                <a href="{{ route('aktivitas.index') }}" class="{{ $tombolSekunder }} mt-5">Reset filter</a>
            @else
                <h2 class="mt-5 text-base font-semibold text-[#0B2E1D]">Belum ada aktivitas yang tercatat</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-[#6B7A72]">Pindai QR di tong sampah untuk mencatat aktivitas pemilahan pertamamu.</p>
                <a href="{{ url('/pindai') }}" class="{{ $tombolPrimer }} mt-5">Buka QR ID Saya</a>
            @endif
        </div>
    @else
        {{-- Tabel (desktop) --}}
        <div class="hidden md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#EEF2F0] text-xs font-medium text-[#6B7A72]">
                    <tr>
                        <th class="px-5 py-3 font-medium">ID</th>
                        <th class="px-5 py-3 font-medium">Tanggal &amp; Waktu</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 font-medium">Lokasi</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Poin</th>
                        <th class="w-10 px-3 py-3"><span class="sr-only">Buka</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF2F0]">
                    @foreach ($aktivitas as $item)
                        <tr class="group relative transition-colors hover:bg-[#F7FAF8]">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('aktivitas.show', $item) }}" class="font-semibold text-[#0B2E1D] after:absolute after:inset-0 focus:outline-none focus-visible:underline">{{ $kodeAkt($item) }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-[#4B5B53] tabular-nums">{{ $item->tanggal_waktu->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-2 text-[#0F2A1C]">
                                    <span class="h-2 w-2 rounded-full ring-2 ring-white" style="background-color: {{ $item->kategori->warna_label ?? '#94A3B8' }}"></span>
                                    {{ $item->kategori->nama_kategori ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-[#4B5B53]">{{ $item->lokasi->nama_lokasi ?? '-' }}</td>
                            <td class="px-5 py-3.5">@include('partials.badge-status', ['status' => $item->status_verifikasi])</td>
                            <td class="px-5 py-3.5 text-right font-semibold tabular-nums {{ $item->status_verifikasi === 'Terverifikasi' ? 'text-[#15803D]' : 'text-[#9AA8A0]' }}">{{ $poinTeks($item) }}</td>
                            <td class="px-3 py-3.5 text-[#B5C2BB] transition group-hover:translate-x-0.5 group-hover:text-[#16A34A]">@include('partials.ikon', ['n' => 'panah', 'c' => 'h-4 w-4'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Kartu (HP) --}}
        <ul class="divide-y divide-[#EEF2F0] md:hidden">
            @foreach ($aktivitas as $item)
                <li>
                    <a href="{{ route('aktivitas.show', $item) }}" class="block px-4 py-4 transition-colors hover:bg-[#F7FAF8]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-[#0B2E1D]">{{ $kodeAkt($item) }}</p>
                                <p class="mt-0.5 text-xs text-[#6B7A72] tabular-nums">{{ $item->tanggal_waktu->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                            @include('partials.badge-status', ['status' => $item->status_verifikasi])
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="inline-flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $item->kategori->warna_label ?? '#94A3B8' }}"></span>
                                    {{ $item->kategori->nama_kategori ?? '-' }}
                                </p>
                                <p class="truncate text-xs text-[#6B7A72]">{{ $item->lokasi->nama_lokasi ?? '-' }}</p>
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums {{ $item->status_verifikasi === 'Terverifikasi' ? 'text-[#15803D]' : 'text-[#9AA8A0]' }}">{{ $poinTeks($item) }} poin</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($aktivitas->hasPages())
            <div class="border-t border-[#EEF2F0] px-4 py-3 sm:px-5">{{ $aktivitas->links() }}</div>
        @endif
    @endif
</section>
@endsection
