@extends('layouts.app')

@section('judul', 'Detail Verifikasi · PilahKu')

@section('isi')
    <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Detail Verifikasi</h1>
            <a href="{{ route('admin.verifikasi.index') }}" class="text-sm text-slate-500 hover:underline">← Kembali ke daftar</a>
        </div>

        <img src="{{ Storage::disk('public')->url($aktivitas->foto_bukti) }}" alt="Foto sampah"
             class="max-h-72 w-full rounded-xl object-cover ring-1 ring-slate-200">

        <dl class="grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
            <dt class="text-slate-500">Pengguna</dt>
            <dd class="col-span-2">{{ $aktivitas->pengguna->nama_lengkap }}</dd>

            <dt class="text-slate-500">Lokasi</dt>
            <dd class="col-span-2">{{ $aktivitas->lokasi->nama_lokasi }}</dd>

            <dt class="text-slate-500">Waktu</dt>
            <dd class="col-span-2">{{ $aktivitas->tanggal_waktu->translatedFormat('d F Y, H:i') }}</dd>

            <dt class="text-slate-500">Label terdeteksi</dt>
            <dd class="col-span-2" data-label>{{ $tahap1?->label_terdeteksi ?? '—' }}</dd>

            <dt class="text-slate-500">Skor keyakinan</dt>
            <dd class="col-span-2" data-skor>{{ $tahap1?->skor_keyakinan !== null ? number_format($tahap1->skor_keyakinan, 2) : '—' }}</dd>

            <dt class="text-slate-500">Kategori</dt>
            <dd class="col-span-2">{{ $aktivitas->kategori?->nama_kategori ?? 'Belum teridentifikasi' }}</dd>

            <dt class="text-slate-500">Catatan pengguna</dt>
            <dd class="col-span-2">{{ $aktivitas->catatan ?? '—' }}</dd>
        </dl>

        <form method="POST" action="{{ route('admin.verifikasi.proses', $aktivitas) }}" class="space-y-3 border-t border-slate-200 pt-4">
            @csrf
            <label for="alasan" class="block text-sm font-medium">Alasan <span class="font-normal text-slate-500">(wajib jika menolak)</span></label>
            <textarea id="alasan" name="alasan" rows="3" maxlength="255"
                      class="w-full rounded-lg text-sm ring-1 ring-slate-300">{{ old('alasan') }}</textarea>
            @error('alasan')
                <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
            @error('aksi')
                <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex justify-end gap-3">
                <button type="submit" name="aksi" value="tolak"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Tolak</button>
                <button type="submit" name="aksi" value="setujui"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Setujui</button>
            </div>
        </form>
    </section>
@endsection
