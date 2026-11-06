@extends('layouts.app')

@section('judul', 'Detail Aktivitas · PilahKu')

@section('isi')
    <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-xl font-semibold">Detail Aktivitas Pemilahan</h1>

        <img src="{{ Storage::disk('public')->url($aktivitas->foto_bukti) }}" alt="Foto sampah"
             class="max-h-72 w-full rounded-xl object-cover ring-1 ring-slate-200">

        <dl class="grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
            <dt class="text-slate-500">Kategori</dt>
            <dd class="col-span-2">{{ $aktivitas->kategori?->nama_kategori ?? 'Belum teridentifikasi' }}</dd>

            <dt class="text-slate-500">Lokasi</dt>
            <dd class="col-span-2">{{ $aktivitas->lokasi->nama_lokasi }}</dd>

            <dt class="text-slate-500">Waktu</dt>
            <dd class="col-span-2">{{ $aktivitas->tanggal_waktu->translatedFormat('d F Y, H:i') }}</dd>

            <dt class="text-slate-500">Status</dt>
            <dd class="col-span-2 font-medium" data-status>{{ $aktivitas->status_verifikasi }}</dd>

            <dt class="text-slate-500">Poin</dt>
            <dd class="col-span-2">{{ $aktivitas->poin_diperoleh }}</dd>

            <dt class="text-slate-500">Catatan</dt>
            <dd class="col-span-2" data-catatan>{{ $aktivitas->catatan ?? '—' }}</dd>
        </dl>

        <div class="space-y-2 rounded-xl bg-slate-50 p-4 text-sm" data-hasil-verifikasi>
            <h2 class="font-medium">Hasil Verifikasi</h2>
            @if ($tahap1)
                <p>
                    Tahap 1 (otomatis): <strong>{{ $tahap1->hasil }}</strong>
                    @if ($tahap1->label_terdeteksi)
                        — label {{ $tahap1->label_terdeteksi }}, skor {{ number_format($tahap1->skor_keyakinan, 2) }}
                    @endif
                </p>
            @else
                <p class="text-slate-500">Belum ada hasil verifikasi.</p>
            @endif
            @if ($tahap2)
                <p>Tahap 2 (Admin): <strong>{{ $tahap2->hasil }}</strong>@if ($tahap2->keterangan) — {{ $tahap2->keterangan }}@endif</p>
            @endif
            @if ($aktivitas->alasan_penolakan)
                <p class="text-red-600">Alasan penolakan: {{ $aktivitas->alasan_penolakan }}</p>
            @endif
        </div>

        @if ($bisaDiubah)
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('modal-catatan').showModal()"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-medium ring-1 ring-slate-300 hover:bg-slate-50">
                    Ubah Catatan
                </button>
                <button type="button" onclick="document.getElementById('modal-batal').showModal()"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    Batalkan
                </button>
            </div>
        @endif
    </section>

    @if ($bisaDiubah)
        <dialog id="modal-catatan" class="m-auto w-full max-w-md rounded-2xl p-6 backdrop:bg-black/40">
            <form method="POST" action="{{ route('aktivitas.catatan', $aktivitas) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <h2 class="text-lg font-semibold">Ubah Catatan</h2>
                <label for="catatan" class="sr-only">Catatan</label>
                <textarea id="catatan" name="catatan" rows="3" maxlength="255"
                          class="w-full rounded-lg border-slate-300 text-sm ring-1 ring-slate-300">{{ old('catatan', $aktivitas->catatan) }}</textarea>
                @error('catatan')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg px-4 py-2 text-sm ring-1 ring-slate-300">Batal</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Simpan</button>
                </div>
            </form>
        </dialog>

        <dialog id="modal-batal" class="m-auto w-full max-w-md rounded-2xl p-6 backdrop:bg-black/40">
            <form method="POST" action="{{ route('aktivitas.destroy', $aktivitas) }}" class="space-y-4">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-semibold">Batalkan aktivitas?</h2>
                <p class="text-sm text-slate-600">Aktivitas akan dihapus dan tidak diproses untuk verifikasi.</p>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg px-4 py-2 text-sm ring-1 ring-slate-300">Kembali</button>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white">Ya, Batalkan</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
