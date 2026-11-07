{{--
    PBI-13 · Panel Admin: Verifikasi Manual (tahap 2)
    Variabel dari controller:
        $antrean  Collection<AktivitasPemilahan> berstatus Menunggu Validasi dengan hasil tahap 1 "Ragu"
                  (relasi: pengguna, lokasi, kategori, verifikasi)
    Aksi:
        POST /admin/verifikasi/{id}   field: keputusan = setujui|tolak, alasan (wajib bila tolak)
--}}
@extends('layouts.app')

@section('judul', 'Verifikasi Manual · PilahKu')

@section('breadcrumb')
    <span>Admin</span>
    <span class="mx-2 text-[#B7C4BC]">/</span>
    <span class="font-semibold text-[#0F2A1C]">Verifikasi Manual</span>
@endsection

@section('isi')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Verifikasi Manual</h1>
        <p class="mt-1 text-sm text-[#5B6B62]">Aktivitas yang hasil identifikasi otomatisnya <strong>Ragu</strong> (skor di bawah {{ round(config('pilahku.ambang_skor', 0.7) * 100) }}%). Periksa foto, lalu setujui atau tolak.</p>
    </div>
    <div class="flex gap-3">
        <div class="rounded-2xl border border-[#E1EAE4] bg-white px-4 py-2.5">
            <p class="text-xs text-[#5B6B62]">Dalam antrean</p>
            <p class="text-xl font-bold">{{ count($antrean) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E1EAE4] bg-white px-4 py-2.5">
            <p class="text-xs text-[#5B6B62]">Ambang skor</p>
            <p class="text-xl font-bold">{{ round(config('pilahku.ambang_skor', 0.7) * 100) }}%</p>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="mt-6 rounded-2xl border border-[#FECACA] bg-[#FEF2F2] px-5 py-3 text-sm text-[#B91C1C]">{{ $errors->first() }}</div>
@endif

<section class="mt-6 overflow-hidden rounded-2xl border border-[#E1EAE4] bg-white">
    @forelse ($antrean as $item)
        @php
            $kode = 'AKT-' . str_pad($item->id_aktivitas, 4, '0', STR_PAD_LEFT);
            $v1 = $item->verifikasi->firstWhere('tahap', 1);
            $skor = round(($v1->skor_keyakinan ?? 0) * 100);
        @endphp
        <article class="grid gap-5 border-b border-[#EEF3F0] p-5 last:border-b-0 sm:p-6 lg:grid-cols-[150px_minmax(0,1fr)_auto] lg:items-center">
            {{-- Foto (tautan ke halaman detail verifikasi) --}}
            <a href="{{ route('admin.verifikasi.show', $item) }}" class="block" aria-label="Tinjau detail {{ $kode }}">
                @if ($item->foto_bukti)
                    <img src="{{ asset('storage/' . $item->foto_bukti) }}" alt="Foto {{ $kode }}" class="h-36 w-full rounded-xl bg-[#E8F2EC] object-cover lg:h-28 lg:w-[150px]">
                @else
                    <div class="flex h-36 w-full items-center justify-center rounded-xl bg-[#E8F2EC] text-[#5B8A6C] lg:h-28 lg:w-[150px]">@include('partials.ikon', ['n' => 'kamera', 'c' => 'h-7 w-7'])</div>
                @endif
            </a>

            {{-- Info --}}
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.verifikasi.show', $item) }}" class="font-semibold hover:text-[#15803D] hover:underline">{{ $kode }}</a>
                    <span class="rounded-full bg-[#FEF3C7] px-2 py-0.5 text-xs font-semibold text-[#92400E]">Ragu</span>
                    <span class="text-xs text-[#5B6B62]">{{ $item->tanggal_waktu->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-[#5B6B62]">Pengguna</dt>
                        <dd class="mt-0.5 truncate font-medium">{{ $item->pengguna->nama_lengkap ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#5B6B62]">Lokasi</dt>
                        <dd class="mt-0.5 truncate font-medium">{{ $item->lokasi->nama_lokasi ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#5B6B62]">Label terdeteksi</dt>
                        <dd class="mt-0.5 truncate font-medium">{{ $v1->label_terdeteksi ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#5B6B62]">Keyakinan</dt>
                        <dd class="mt-1 flex items-center gap-2">
                            <span class="h-1.5 w-16 overflow-hidden rounded-full bg-[#E1EAE4]"><span class="block h-full rounded-full bg-[#F59E0B]" style="width: {{ $skor }}%"></span></span>
                            <span class="font-medium">{{ $skor }}%</span>
                        </dd>
                    </div>
                </dl>
                @if ($item->catatan)
                    <p class="mt-3 text-xs text-[#5B6B62]">Catatan pengguna: “{{ $item->catatan }}”</p>
                @endif
            </div>

            {{-- Aksi --}}
            <div class="flex gap-3 lg:flex-col">
                <form method="POST" action="{{ url('/admin/verifikasi/' . $item->id_aktivitas) }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="keputusan" value="setujui">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#16A34A] px-4 text-sm font-semibold text-white hover:bg-[#15803D] lg:w-32">
                        @include('partials.ikon', ['n' => 'centang', 'c' => 'h-4 w-4']) Setujui
                    </button>
                </form>
                <button type="button" onclick="bukaTolak('{{ url('/admin/verifikasi/' . $item->id_aktivitas) }}', '{{ $kode }}')"
                        class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl border border-[#DC2626] px-4 text-sm font-semibold text-[#DC2626] hover:bg-[#FEF2F2] lg:w-32 lg:flex-none">
                    @include('partials.ikon', ['n' => 'silang', 'c' => 'h-4 w-4']) Tolak
                </button>
            </div>
        </article>
    @empty
        <div class="flex flex-col items-center px-6 py-16 text-center">
            <span class="grid h-14 w-14 place-items-center rounded-full bg-[#E8F7EE] text-[#16A34A]">@include('partials.ikon', ['n' => 'perisai', 'c' => 'h-7 w-7'])</span>
            <p class="mt-4 text-base font-semibold">Antrean kosong</p>
            <p class="mt-1 text-sm text-[#5B6B62]">Semua aktivitas sudah terverifikasi otomatis. Tidak ada yang perlu ditinjau.</p>
        </div>
    @endforelse
</section>

{{-- Modal tolak --}}
<dialog id="modal-tolak" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-xl">
    <form id="form-tolak" method="POST" action="" class="p-6 sm:p-7">
        @csrf
        <input type="hidden" name="keputusan" value="tolak">
        <span class="grid h-14 w-14 place-items-center rounded-full bg-[#FEE2E2] text-[#DC2626]">@include('partials.ikon', ['n' => 'silang', 'c' => 'h-7 w-7'])</span>
        <h2 class="mt-4 text-xl font-bold">Tolak aktivitas <span id="kode-tolak"></span>?</h2>
        <p class="mt-2 text-sm text-[#5B6B62]">Pengguna tidak mendapat poin dan akan melihat alasan penolakan di detail aktivitas.</p>
        <label for="alasan" class="mt-5 block text-sm font-semibold">Alasan penolakan <span class="text-[#DC2626]">*</span></label>
        <textarea id="alasan" name="alasan" rows="3" required maxlength="255"
                  class="mt-2 w-full rounded-xl border border-[#D5E2DA] px-4 py-3 text-sm outline-none placeholder:text-[#9AAFA2] focus:border-[#DC2626] focus:ring-4 focus:ring-[#DC2626]/10"
                  placeholder="Contoh: Foto menunjukkan sampah organik di tempat sampah plastik">{{ old('alasan') }}</textarea>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="this.closest('dialog').close()" class="h-11 rounded-xl border border-[#D5E2DA] px-5 text-sm font-semibold hover:bg-[#F3F7F4]">Kembali</button>
            <button type="submit" class="h-11 rounded-xl bg-[#DC2626] px-5 text-sm font-semibold text-white hover:bg-[#B91C1C]">Tolak Aktivitas</button>
        </div>
    </form>
</dialog>
@endsection

@push('skrip')
<script>
    function bukaTolak(aksi, kode) {
        document.getElementById('form-tolak').action = aksi;
        document.getElementById('kode-tolak').textContent = kode;
        document.getElementById('modal-tolak').showModal();
    }
</script>
@endpush
