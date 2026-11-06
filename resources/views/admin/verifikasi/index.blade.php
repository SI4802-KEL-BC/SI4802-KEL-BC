@extends('layouts.app')

@section('judul', 'Verifikasi Manual · PilahKu')

@section('isi')
    <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div>
            <h1 class="text-xl font-semibold">Daftar Verifikasi Manual</h1>
            <p class="mt-1 text-sm text-slate-500">Aktivitas dengan hasil verifikasi otomatis "Ragu" yang menunggu keputusan Admin.</p>
        </div>

        @forelse ($antrean as $aktivitas)
            @php($tahap1 = $aktivitas->verifikasi->firstWhere('tahap', 1))
            <a href="{{ route('admin.verifikasi.show', $aktivitas) }}" data-antrean
               class="flex items-center justify-between rounded-xl p-4 ring-1 ring-slate-200 hover:bg-slate-50">
                <div class="text-sm">
                    <p class="font-medium">{{ $aktivitas->pengguna->nama_lengkap }} · {{ $aktivitas->lokasi->nama_lokasi }}</p>
                    <p class="text-slate-500">
                        {{ $aktivitas->tanggal_waktu->translatedFormat('d F Y, H:i') }}
                        · label {{ $tahap1?->label_terdeteksi ?? '—' }}
                        · skor {{ $tahap1?->skor_keyakinan !== null ? number_format($tahap1->skor_keyakinan, 2) : '—' }}
                    </p>
                </div>
                <span class="text-sm font-medium text-emerald-700">Tinjau</span>
            </a>
        @empty
            <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500" data-kosong>Tidak ada aktivitas yang perlu diverifikasi.</p>
        @endforelse
    </section>
@endsection
