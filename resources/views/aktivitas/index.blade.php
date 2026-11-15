{{--
    PBI-14 · Riwayat Aktivitas Pemilahan (GET /aktivitas)
    Variabel dari controller:
        $aktivitas       LengthAwarePaginator<AktivitasPemilahan> (relasi: kategori, lokasi), 10 per halaman
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
        ['label' => 'Total Aktivitas', 'angka' => $ringkasan['total'], 'warna' => 'text-[#0F2A1C]'],
        ['label' => 'Terverifikasi', 'angka' => $ringkasan['terverifikasi'], 'warna' => 'text-[#15803D]'],
        ['label' => 'Menunggu Validasi', 'angka' => $ringkasan['menunggu'], 'warna' => 'text-[#92400E]'],
        ['label' => 'Ditolak', 'angka' => $ringkasan['ditolak'], 'warna' => 'text-[#B91C1C]'],
    ];
    $kodeAkt = fn ($a) => 'AKT-' . str_pad($a->id_aktivitas, 4, '0', STR_PAD_LEFT);
    $poinTeks = fn ($a) => match ($a->status_verifikasi) {
        'Terverifikasi' => '+' . $a->poin_diperoleh,
        'Ditolak' => '0',
        default => '–',
    };
    $inputKelas = 'h-11 w-full rounded-xl border border-[#E1EAE4] bg-white px-3 text-sm outline-none focus:border-[#16A34A] focus:ring-2 focus:ring-[#16A34A]/20';
@endphp

<div>
    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Aktivitas Saya</h1>
    <p class="mt-1 text-sm text-[#5B6B62]">Riwayat aktivitas pemilahan sampahmu, dari yang terbaru.</p>
</div>

{{-- Ringkasan --}}
<section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan aktivitas">
    @foreach ($kartu as $k)
        <div class="rounded-2xl border border-[#E1EAE4] bg-white px-4 py-3">
            <p class="text-xs text-[#5B6B62]">{{ $k['label'] }}</p>
            <p class="mt-0.5 text-2xl font-bold {{ $k['warna'] }}">{{ $k['angka'] }}</p>
        </div>
    @endforeach
</section>

{{-- Pesan validasi filter --}}
@if ($errors->any())
    <div role="alert" class="mt-6 rounded-2xl border border-[#FECACA] bg-[#FEF2F2] px-5 py-3 text-sm text-[#B91C1C]">
        <ul class="space-y-0.5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Filter --}}
<form method="GET" action="{{ route('aktivitas.index') }}" class="mt-6 rounded-2xl border border-[#E1EAE4] bg-white p-4 sm:p-5" aria-label="Filter riwayat">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-[#5B6B62]">Dari tanggal</span>
            <input type="date" name="dari" value="{{ $nilai('dari') }}" class="{{ $inputKelas }}">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-[#5B6B62]">Sampai tanggal</span>
            <input type="date" name="sampai" value="{{ $nilai('sampai') }}" class="{{ $inputKelas }}">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-[#5B6B62]">Kategori</span>
            <select name="kategori" class="{{ $inputKelas }}">
                <option value="">Semua kategori</option>
                @foreach ($daftarKategori as $kat)
                    <option value="{{ $kat->id_kategori }}" @selected((string) $nilai('kategori') === (string) $kat->id_kategori)>{{ $kat->nama_kategori }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-[#5B6B62]">Status</span>
            <select name="status" class="{{ $inputKelas }}">
                <option value="">Semua status</option>
                @foreach ($daftarStatus as $st)
                    <option value="{{ $st }}" @selected($nilai('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <div class="mt-4 flex flex-wrap gap-3">
        <button type="submit" class="h-11 rounded-xl bg-[#16A34A] px-5 text-sm font-semibold text-white hover:bg-[#15803D]">Terapkan</button>
        @if ($adaFilter || $errors->any())
            <a href="{{ route('aktivitas.index') }}" class="inline-flex h-11 items-center rounded-xl border border-[#E1EAE4] px-5 text-sm font-semibold text-[#0F3D26] hover:bg-[#F3F7F4]">Reset filter</a>
        @endif
    </div>
</form>

{{-- Daftar --}}
@if ($aktivitas->isEmpty())
    <section class="mt-6 rounded-2xl border border-[#E1EAE4] bg-white px-6 py-14 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-[#E8F7EE] text-[#16A34A]">
            @include('partials.ikon', ['n' => $adaFilter ? 'daftar' : 'kotak-masuk', 'c' => 'h-7 w-7'])
        </div>
        @if ($adaFilter)
            <h2 class="mt-4 text-lg font-semibold">Tidak ada aktivitas sesuai filter</h2>
            <p class="mt-1 text-sm text-[#5B6B62]">Coba ubah rentang tanggal, kategori, atau status.</p>
            <a href="{{ route('aktivitas.index') }}" class="mt-5 inline-flex h-11 items-center rounded-xl bg-[#16A34A] px-5 text-sm font-semibold text-white hover:bg-[#15803D]">Reset filter</a>
        @else
            <h2 class="mt-4 text-lg font-semibold">Belum ada aktivitas yang tercatat</h2>
            <p class="mt-1 text-sm text-[#5B6B62]">Pindai QR di tong sampah untuk mencatat aktivitas pemilahan pertamamu.</p>
            <a href="{{ url('/pindai') }}" class="mt-5 inline-flex h-11 items-center rounded-xl bg-[#16A34A] px-5 text-sm font-semibold text-white hover:bg-[#15803D]">Buka QR ID Saya</a>
        @endif
    </section>
@else
    {{-- Tabel (desktop) --}}
    <section class="mt-6 hidden overflow-hidden rounded-2xl border border-[#E1EAE4] bg-white md:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#F8FBF9] text-xs uppercase tracking-wide text-[#5B6B62]">
                <tr>
                    <th class="px-5 py-3 font-semibold">ID</th>
                    <th class="px-5 py-3 font-semibold">Tanggal &amp; Waktu</th>
                    <th class="px-5 py-3 font-semibold">Kategori</th>
                    <th class="px-5 py-3 font-semibold">Lokasi</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 text-right font-semibold">Poin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#EEF3F0]">
                @foreach ($aktivitas as $item)
                    <tr class="hover:bg-[#F8FBF9]">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('aktivitas.show', $item) }}" class="font-semibold text-[#0F3D26] hover:text-[#15803D] hover:underline">{{ $kodeAkt($item) }}</a>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-[#5B6B62]">{{ $item->tanggal_waktu->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item->kategori->warna_label ?? '#94A3B8' }}"></span>
                                {{ $item->kategori->nama_kategori ?? '-' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">{{ $item->lokasi->nama_lokasi ?? '-' }}</td>
                        <td class="px-5 py-3.5">@include('partials.badge-status', ['status' => $item->status_verifikasi])</td>
                        <td class="px-5 py-3.5 text-right font-semibold {{ $item->status_verifikasi === 'Terverifikasi' ? 'text-[#15803D]' : 'text-[#5B6B62]' }}">{{ $poinTeks($item) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- Kartu (HP) --}}
    <section class="mt-6 space-y-3 md:hidden">
        @foreach ($aktivitas as $item)
            <a href="{{ route('aktivitas.show', $item) }}" class="block rounded-2xl border border-[#E1EAE4] bg-white p-4 hover:border-[#16A34A]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-[#0F3D26]">{{ $kodeAkt($item) }}</p>
                        <p class="text-xs text-[#5B6B62]">{{ $item->tanggal_waktu->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    @include('partials.badge-status', ['status' => $item->status_verifikasi])
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item->kategori->warna_label ?? '#94A3B8' }}"></span>
                            {{ $item->kategori->nama_kategori ?? '-' }}
                        </p>
                        <p class="truncate text-xs text-[#5B6B62]">{{ $item->lokasi->nama_lokasi ?? '-' }}</p>
                    </div>
                    <p class="shrink-0 font-semibold {{ $item->status_verifikasi === 'Terverifikasi' ? 'text-[#15803D]' : 'text-[#5B6B62]' }}">{{ $poinTeks($item) }} poin</p>
                </div>
            </a>
        @endforeach
    </section>

    @if ($aktivitas->hasPages())
        <div class="mt-6">{{ $aktivitas->links() }}</div>
    @endif
@endif
@endsection
