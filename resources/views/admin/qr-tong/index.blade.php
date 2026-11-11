{{--
    PBI-13 · Admin: QR Tong Sampah (untuk dicetak dan ditempel di tong)
    Variabel dari controller:
        $daftar  Collection<array{lokasi: LokasiTempatSampah, url: string, svg: string}>
--}}
@extends('layouts.app')

@section('judul', 'QR Tong Sampah · PilahKu')

@section('breadcrumb')
    <span>Admin</span>
    <span class="mx-2 text-[#B7C4BC]">/</span>
    <span class="font-semibold text-[#0F2A1C]">QR Tong Sampah</span>
@endsection

@section('isi')
{{-- Saat dicetak: sembunyikan sidebar, topbar, dan elemen non-QR --}}
<style>
    @media print {
        #sidebar, #lapisan-sidebar, header, .tanpa-cetak { display: none !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        main { max-width: none !important; padding: 0 !important; }
        .kartu-qr { break-inside: avoid; border-color: #999 !important; box-shadow: none !important; }
    }
</style>

<div class="tanpa-cetak flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">QR Tong Sampah</h1>
        <p class="mt-1 text-sm text-[#5B6B62]">Cetak QR ini dan tempel di tong yang bersangkutan. Pengguna memindainya untuk memulai sesi pembuangan di tong itu.</p>
    </div>
    <button type="button" onclick="window.print()"
            class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#16A34A] px-5 text-sm font-semibold text-white transition hover:bg-[#15803D]">
        Cetak semua
    </button>
</div>

@if (! config('pilahku.qr_base_url'))
    <div class="tanpa-cetak mt-6 flex items-start gap-3 rounded-2xl border border-[#FDE68A] bg-[#FFFBEB] px-5 py-4 text-sm">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#F59E0B] text-white">@include('partials.ikon', ['n' => 'peringatan', 'c' => 'h-4 w-4'])</span>
        <p class="text-[#92400E]">QR berisi alamat <strong>{{ url('/') }}</strong>. Agar bisa dipindai dari HP atau perangkat lain, isi <code>PILAHKU_QR_BASE_URL</code> di <code>.env</code>
            dengan alamat yang bisa dijangkau (mis. IP laptop di jaringan yang sama, atau domain produksi), lalu muat ulang halaman ini.</p>
    </div>
@endif

<div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($daftar as $item)
        @php $lokasi = $item['lokasi']; @endphp
        <article class="kartu-qr rounded-2xl border border-[#E1EAE4] bg-white p-6 text-center shadow-[0_1px_2px_rgba(15,42,28,.04)]">
            <div class="mx-auto w-full max-w-[240px] [&>svg]:h-auto [&>svg]:w-full" aria-label="QR tong {{ $lokasi->kode_perangkat }}">{!! $item['svg'] !!}</div>

            <p class="mt-4 text-lg font-bold">{{ $lokasi->nama_lokasi }}</p>
            <p class="mt-1 font-mono text-sm font-semibold tracking-wide text-[#15803D]">{{ $lokasi->kode_perangkat }}</p>
            <p class="mt-2 text-xs text-[#5B6B62]">
                Menerima: {{ $lokasi->kategoriDiterima->pluck('nama_kategori')->join(', ') ?: '—' }}
            </p>
            @if ($lokasi->status !== 'aktif')
                <p class="mt-2 inline-block rounded-full bg-[#FEE2E2] px-2.5 py-1 text-xs font-semibold text-[#B91C1C]">Tong nonaktif</p>
            @endif
            <p class="tanpa-cetak mt-3 break-all rounded-lg bg-[#F3F7F4] px-3 py-2 font-mono text-[11px] text-[#5B6B62]">{{ $item['url'] }}</p>
        </article>
    @empty
        <div class="tanpa-cetak rounded-2xl border border-dashed border-[#D5E2DA] px-6 py-14 text-center text-sm text-[#5B6B62] sm:col-span-2 xl:col-span-3">
            Belum ada tong sampah terdaftar.
        </div>
    @endforelse
</div>
@endsection
