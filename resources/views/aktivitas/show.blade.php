{{--
    PBI-13 · Detail Aktivitas (+ modal Ubah Catatan & Konfirmasi Batal)
    Variabel dari controller:
        $aktivitas  AktivitasPemilahan dengan relasi: kategori, lokasi, sesi, verifikasi (+ verifikasi.verifikator)
    Aksi (hanya pemilik & status Menunggu Validasi, dicek juga oleh AktivitasPolicy):
        PATCH  /aktivitas/{id}/catatan   field: catatan (nullable, max 255)
        DELETE /aktivitas/{id}
--}}
@extends('layouts.app')

@php
    $kode = 'AKT-' . str_pad($aktivitas->id_aktivitas, 4, '0', STR_PAD_LEFT);
    $status = $aktivitas->status_verifikasi;
    $menunggu = $status === 'Menunggu Validasi';
    $bolehUbah = $menunggu && auth()->user()?->can('update', $aktivitas);
    $v1 = $aktivitas->verifikasi->firstWhere('tahap', 1);
    $v2 = $aktivitas->verifikasi->firstWhere('tahap', 2);
    $ragu = $v1 && $v1->hasil === 'Ragu';
    $warnaKategori = $aktivitas->kategori->warna_label ?? '#94A3B8';
@endphp

@section('judul', 'Detail ' . $kode . ' · PilahKu')

@section('breadcrumb')
    <a href="{{ url('/pindai') }}" class="hover:text-[#0F2A1C]">Aktivitas</a>
    <span class="mx-2 text-[#B7C4BC]">/</span>
    <span class="font-semibold text-[#0F2A1C]">Detail {{ $kode }}</span>
@endsection

@section('isi')
{{-- Judul + aksi --}}
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Detail Aktivitas {{ $kode }}</h1>
            @include('partials.badge-status', ['status' => $status])
        </div>
        <p class="mt-1 text-sm text-[#5B6B62]">Tercatat pada {{ $aktivitas->tanggal_waktu->translatedFormat('d M Y, H:i') }} melalui pemindaian QR ID</p>
    </div>

    @if ($bolehUbah)
        <div class="flex gap-3">
            <button type="button" onclick="document.getElementById('modal-batal').showModal()"
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-[#DC2626] bg-white px-4 text-sm font-semibold text-[#DC2626] transition hover:bg-[#FEF2F2]">
                @include('partials.ikon', ['n' => 'sampah', 'c' => 'h-4 w-4'])
                Batalkan Aktivitas
            </button>
            <button type="button" onclick="document.getElementById('modal-catatan').showModal()"
                    class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#16A34A] px-4 text-sm font-semibold text-white transition hover:bg-[#15803D]">
                @include('partials.ikon', ['n' => 'pensil', 'c' => 'h-4 w-4'])
                Ubah Catatan
            </button>
        </div>
    @endif
</div>

{{-- Banner status --}}
@if ($status === 'Terverifikasi')
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-[#BBE5CA] bg-[#E8F7EE] px-5 py-4">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#16A34A] text-white">@include('partials.ikon', ['n' => 'centang', 'c' => 'h-4 w-4'])</span>
        <div class="text-sm">
            <p class="font-semibold text-[#15803D]">Aktivitas terverifikasi · +{{ $aktivitas->poin_diperoleh }} poin</p>
            <p class="mt-0.5">Terima kasih sudah memilah sampah dengan benar. Poin sudah masuk ke saldomu.</p>
        </div>
    </div>
@elseif ($status === 'Ditolak')
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-[#FECACA] bg-[#FEF2F2] px-5 py-4">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#DC2626] text-white">@include('partials.ikon', ['n' => 'silang', 'c' => 'h-4 w-4'])</span>
        <div class="text-sm">
            <p class="font-semibold text-[#B91C1C]">Aktivitas ditolak</p>
            <p class="mt-0.5">Alasan: {{ $aktivitas->alasan_penolakan ?? '-' }}. Aktivitas ini tidak mendapat poin.</p>
        </div>
    </div>
@elseif ($ragu)
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-[#FDE68A] bg-[#FFFBEB] px-5 py-4">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#F59E0B] text-white">@include('partials.ikon', ['n' => 'peringatan', 'c' => 'h-4 w-4'])</span>
        <div class="text-sm">
            <p class="font-semibold text-[#92400E]">Sedang ditinjau Admin</p>
            <p class="mt-0.5">Hasil identifikasi otomatis belum cukup yakin, jadi Admin akan memeriksa foto sampahmu. Kamu masih bisa mengubah catatan atau membatalkan aktivitas.</p>
        </div>
    </div>
@else
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-[#FDE68A] bg-[#FFFBEB] px-5 py-4">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#F59E0B] text-white">@include('partials.ikon', ['n' => 'jam', 'c' => 'h-4 w-4'])</span>
        <div class="text-sm">
            <p class="font-semibold text-[#92400E]">Menunggu validasi</p>
            <p class="mt-0.5">Aktivitas sedang diproses. Kamu masih bisa mengubah catatan atau membatalkan aktivitas.</p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="mt-4 rounded-2xl border border-[#FECACA] bg-[#FEF2F2] px-5 py-3 text-sm text-[#B91C1C]">{{ $errors->first() }}</div>
@endif

<div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    {{-- Informasi aktivitas --}}
    <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6 sm:p-7">
        <h2 class="text-lg font-semibold">Informasi Aktivitas</h2>

        <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3">
            <div>
                <dt class="text-xs text-[#5B6B62]">Kategori</dt>
                <dd class="mt-1 flex items-center gap-2 text-sm font-semibold">
                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $warnaKategori }}"></span>
                    {{ $aktivitas->kategori->nama_kategori ?? 'Belum dikenali' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-[#5B6B62]">Lokasi Tempat Sampah</dt>
                <dd class="mt-1 text-sm font-semibold">{{ $aktivitas->lokasi->nama_lokasi ?? '-' }}@if ($aktivitas->lokasi?->kode_titik) – {{ $aktivitas->lokasi->kode_titik }}@endif</dd>
            </div>
            <div>
                <dt class="text-xs text-[#5B6B62]">Tanggal &amp; Waktu</dt>
                <dd class="mt-1 text-sm font-semibold">{{ $aktivitas->tanggal_waktu->translatedFormat('d M Y, H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-[#5B6B62]">Jumlah</dt>
                <dd class="mt-1 text-sm font-semibold">{{ rtrim(rtrim(number_format($aktivitas->jumlah, 2, ',', '.'), '0'), ',') }} item</dd>
            </div>
            <div>
                <dt class="text-xs text-[#5B6B62]">ID Aktivitas</dt>
                <dd class="mt-1 text-sm font-semibold">{{ $kode }}</dd>
            </div>
            <div>
                <dt class="text-xs text-[#5B6B62]">Poin</dt>
                <dd class="mt-1 text-sm font-semibold {{ $status === 'Terverifikasi' ? 'text-[#15803D]' : '' }}">
                    {{ $status === 'Terverifikasi' ? '+' . $aktivitas->poin_diperoleh : ($status === 'Ditolak' ? '0' : '–') }}
                </dd>
            </div>
        </dl>

        @if ($v1)
            <div class="mt-6 rounded-xl bg-[#F6FAF7] p-4">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <span class="text-[#16A34A]">@include('partials.ikon', ['n' => 'kilau', 'c' => 'h-4 w-4'])</span>
                    Hasil identifikasi otomatis
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <span>Label: <strong>{{ $v1->label_terdeteksi ?? '-' }}</strong></span>
                    <span class="flex items-center gap-2">Keyakinan:
                        <span class="h-1.5 w-24 overflow-hidden rounded-full bg-[#E1EAE4]"><span class="block h-full rounded-full {{ $ragu ? 'bg-[#F59E0B]' : ($v1->hasil === 'Valid' ? 'bg-[#16A34A]' : 'bg-[#DC2626]') }}" style="width: {{ round(($v1->skor_keyakinan ?? 0) * 100) }}%"></span></span>
                        <strong>{{ round(($v1->skor_keyakinan ?? 0) * 100) }}%</strong>
                    </span>
                </div>
            </div>
        @endif

        <div class="mt-6 border-t border-[#E9F0EC] pt-5">
            <div class="flex items-center justify-between">
                <h3 class="text-xs text-[#5B6B62]">Catatan</h3>
                @if ($bolehUbah)
                    <button type="button" onclick="document.getElementById('modal-catatan').showModal()" class="text-xs font-semibold text-[#15803D] hover:underline">Ubah</button>
                @endif
            </div>
            <p class="mt-1 text-sm {{ $aktivitas->catatan ? '' : 'italic text-[#9AAFA2]' }}">{{ $aktivitas->catatan ?: 'Belum ada catatan.' }}</p>
        </div>

        <div class="mt-5">
            <h3 class="text-xs text-[#5B6B62]">Foto dari kamera tempat sampah</h3>
            @if ($aktivitas->foto_bukti)
                <img src="{{ asset('storage/' . $aktivitas->foto_bukti) }}" alt="Foto sampah {{ $kode }}" class="mt-2 max-h-72 w-full rounded-xl bg-[#E8F2EC] object-contain">
            @else
                <div class="mt-2 flex h-40 flex-col items-center justify-center gap-2 rounded-xl bg-[#E8F2EC] text-[#5B8A6C]">
                    @include('partials.ikon', ['n' => 'kamera', 'c' => 'h-7 w-7'])
                    <p class="text-xs">Foto diambil otomatis oleh perangkat</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Kolom kanan --}}
    <div class="space-y-6">
        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <h2 class="text-base font-semibold">Status Verifikasi</h2>
            @php
                $langkah = [
                    ['QR dipindai', $aktivitas->sesi?->waktu_scan?->format('d M Y, H:i') ?? 'Sesi pembuangan dimulai', 'selesai'],
                    ['Foto sampah diterima', $aktivitas->tanggal_waktu->format('d M Y, H:i'), 'selesai'],
                    ['Verifikasi otomatis', $v1 ? ($v1->hasil . ' · ' . ($v1->label_terdeteksi ?? '-')) : 'Sedang diproses', $v1 ? ($ragu ? 'peringatan' : ($v1->hasil === 'Valid' ? 'selesai' : 'gagal')) : 'berjalan'],
                ];
                if ($ragu) {
                    $langkah[] = ['Verifikasi Admin', $v2 ? ($v2->hasil . ($v2->verifikator?->nama_lengkap ? ' · ' . $v2->verifikator->nama_lengkap : '')) : 'Menunggu peninjauan Admin', $v2 ? ($v2->hasil === 'Valid' ? 'selesai' : 'gagal') : 'berjalan'];
                }
                $langkah[] = match ($status) {
                    'Terverifikasi' => ['Hasil: Terverifikasi', '+' . $aktivitas->poin_diperoleh . ' poin ditambahkan', 'selesai'],
                    'Ditolak' => ['Hasil: Ditolak', 'Tanpa poin', 'gagal'],
                    default => ['Hasil validasi', 'Poin ditambahkan jika terverifikasi', 'nanti'],
                };
                $gayaTitik = [
                    'selesai' => ['bg-[#16A34A] text-white', 'centang'],
                    'gagal' => ['bg-[#DC2626] text-white', 'silang'],
                    'peringatan' => ['bg-[#F59E0B] text-white', 'peringatan'],
                    'berjalan' => ['bg-[#F59E0B] text-white', 'jam'],
                    'nanti' => ['bg-[#E8EFEA] text-[#9AAFA2]', null],
                ];
            @endphp
            <ol class="mt-5">
                @foreach ($langkah as $i => [$judulLangkah, $ketLangkah, $keadaan])
                    <li class="relative flex gap-3 pb-5 last:pb-0">
                        @if (! $loop->last)
                            <span class="absolute top-7 left-[13px] h-[calc(100%-1.5rem)] w-0.5 {{ $keadaan === 'selesai' ? 'bg-[#16A34A]' : 'bg-[#E1EAE4]' }}"></span>
                        @endif
                        <span class="relative grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $gayaTitik[$keadaan][0] }}">
                            @if ($gayaTitik[$keadaan][1])
                                @include('partials.ikon', ['n' => $gayaTitik[$keadaan][1], 'c' => 'h-3.5 w-3.5'])
                            @endif
                        </span>
                        <div class="pt-0.5">
                            <p class="text-sm font-semibold {{ $keadaan === 'nanti' ? 'text-[#9AAFA2]' : '' }}">{{ $judulLangkah }}</p>
                            <p class="text-xs text-[#5B6B62]">{{ $ketLangkah }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
            <h2 class="text-base font-semibold">Yang bisa kamu lakukan</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @if ($menunggu)
                    <li class="flex gap-2.5"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#16A34A]"></span><span>Ubah catatan selama status masih Menunggu Validasi.</span></li>
                    <li class="flex gap-2.5"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#16A34A]"></span><span>Batalkan aktivitas jika salah pindai. Aktivitas yang dibatalkan dihapus dan tidak diproses.</span></li>
                @endif
                <li class="flex gap-2.5"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#F59E0B]"></span><span>Setelah terverifikasi atau ditolak, aktivitas terkunci dan tidak bisa diubah.</span></li>
            </ul>
        </section>
    </div>
</div>

{{-- Modal ubah catatan --}}
@if ($bolehUbah)
    <dialog id="modal-catatan" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl p-0 shadow-xl">
        <form method="POST" action="{{ url('/aktivitas/' . $aktivitas->id_aktivitas . '/catatan') }}" class="p-6 sm:p-7">
            @csrf
            @method('PATCH')
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Ubah Catatan</h2>
                    <p class="mt-1 text-sm text-[#5B6B62]">Tambahkan keterangan untuk aktivitas {{ $kode }}.</p>
                </div>
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1 text-[#5B6B62] hover:bg-[#F3F7F4]" aria-label="Tutup">@include('partials.ikon', ['n' => 'silang', 'c' => 'h-5 w-5'])</button>
            </div>
            <label for="catatan" class="mt-5 block text-sm font-semibold">Catatan <span class="font-normal text-[#5B6B62]">(opsional)</span></label>
            <textarea id="catatan" name="catatan" rows="4" maxlength="255"
                      class="mt-2 w-full rounded-xl border border-[#D5E2DA] px-4 py-3 text-sm outline-none placeholder:text-[#9AAFA2] focus:border-[#16A34A] focus:ring-4 focus:ring-[#16A34A]/15"
                      placeholder="Contoh: botol plastik sudah dibilas dan labelnya dilepas">{{ old('catatan', $aktivitas->catatan) }}</textarea>
            <p class="mt-1 text-right text-xs text-[#9AAFA2]">Maks. 255 karakter</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" onclick="this.closest('dialog').close()" class="h-11 rounded-xl border border-[#D5E2DA] px-5 text-sm font-semibold hover:bg-[#F3F7F4]">Kembali</button>
                <button type="submit" class="h-11 rounded-xl bg-[#16A34A] px-5 text-sm font-semibold text-white hover:bg-[#15803D]">Simpan Catatan</button>
            </div>
        </form>
    </dialog>

    {{-- Modal konfirmasi batal --}}
    <dialog id="modal-batal" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-xl">
        <form method="POST" action="{{ url('/aktivitas/' . $aktivitas->id_aktivitas) }}" class="p-6 sm:p-7">
            @csrf
            @method('DELETE')
            <span class="grid h-14 w-14 place-items-center rounded-full bg-[#FEE2E2] text-[#DC2626]">@include('partials.ikon', ['n' => 'peringatan', 'c' => 'h-7 w-7'])</span>
            <h2 class="mt-4 text-xl font-bold">Batalkan aktivitas ini?</h2>
            <p class="mt-2 text-sm leading-relaxed text-[#5B6B62]">
                Aktivitas {{ $kode }} ({{ $aktivitas->kategori->nama_kategori ?? 'sampah' }}, {{ $aktivitas->lokasi->nama_lokasi ?? '-' }}) akan dihapus dan tidak diproses. Tindakan ini tidak dapat diurungkan.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="this.closest('dialog').close()" class="h-11 rounded-xl border border-[#D5E2DA] px-5 text-sm font-semibold hover:bg-[#F3F7F4]">Kembali</button>
                <button type="submit" class="h-11 rounded-xl bg-[#DC2626] px-5 text-sm font-semibold text-white hover:bg-[#B91C1C]">Ya, Batalkan</button>
            </div>
        </form>
    </dialog>
@endif
@endsection

@if ($errors->has('catatan'))
    @push('skrip')
        <script>document.getElementById('modal-catatan')?.showModal();</script>
    @endpush
@endif
