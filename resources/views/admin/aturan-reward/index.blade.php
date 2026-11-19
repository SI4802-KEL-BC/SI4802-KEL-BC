@extends('layouts.app')

@section('judul', 'Aturan Reward Point · PilahKu')

@section('breadcrumb')
    <span>Admin</span><span class="mx-2 text-[#B7C4BC]">/</span><span class="font-semibold text-[#0F2A1C]">Aturan Reward Point</span>
@endsection

@section('isi')
@php
    $modeTambah = request()->boolean('tambah') || (! $terpilih && $errors->any());
    $formAktif = $modeTambah || $terpilih;
    $nilaiAwal = old('nilai_poin', $terpilih ? abs($terpilih->poin_per_satuan) : 5);
    $syaratAktif = old('syarat', $terpilih?->syarat ?? ['aktivitas_terverifikasi']);
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-[#0B2E1D] sm:text-[28px]">Aturan Reward Point</h1>
        <p class="mt-1 text-sm text-[#6B7A72]">Tentukan nilai dan syarat poin untuk aktivitas yang memenuhi ketentuan.</p>
    </div>
    <a href="{{ route('admin.aturan-reward.index', ['tambah' => 1]) }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#16A34A] px-5 text-sm font-semibold text-white hover:bg-[#15803D]">＋ Tambah Aturan</a>
</div>

@if (session('status'))
    <div class="mt-5 rounded-xl border border-[#86EFAC] bg-[#E8F7EE] px-5 py-3 text-sm font-medium text-[#15803D]">{{ session('status') }}</div>
@endif
<div class="mt-6 flex items-start gap-3 rounded-xl border border-[#86EFAC] bg-[#E8F7EE] px-5 py-4 text-sm">
    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#16A34A] font-bold text-white">i</span>
    <div><p class="font-semibold text-[#15803D]">Aturan hanya berlaku untuk aktivitas terverifikasi</p><p class="mt-0.5">Perubahan nilai poin berlaku untuk aktivitas berikutnya dan tidak mengubah transaksi yang sudah tercatat.</p></div>
</div>

<div class="mt-6 grid items-start gap-6 {{ $formAktif ? 'xl:grid-cols-[minmax(0,1.65fr)_400px]' : 'xl:grid-cols-1' }}">
    <section class="overflow-hidden rounded-2xl border border-[#E4EBE7] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-left text-[13px]">
                <thead class="bg-[#F5F8F6] text-xs uppercase text-[#607168]"><tr><th class="px-5 py-3">Kode</th><th class="px-4 py-3">Aturan</th><th class="px-4 py-3">Nilai</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($aturan as $item)
                        <tr class="border-t border-[#E4EBE7] {{ $terpilih?->id_aturan === $item->id_aturan ? 'bg-[#E8F7EE]' : '' }}">
                            <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $item->kode_aturan }}</td>
                            <td class="px-4 py-3"><p class="font-semibold">{{ $item->nama_aturan }}</p><p class="mt-0.5 text-xs text-[#6B7A72]">{{ $item->kategori?->nama_kategori ?? 'Semua kategori' }} · {{ implode(' · ', array_map(fn ($s) => $syaratPilihan[$s] ?? $s, $item->syarat ?? [])) }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold {{ $item->poin_per_satuan < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $item->satuan === 'pengali' ? '×'.abs($item->poin_per_satuan) : (($item->poin_per_satuan > 0 ? '+' : '−').abs($item->poin_per_satuan).($item->satuan === 'flat' ? '' : ' / '.$item->satuan)) }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->status === 'aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($item->status) }}</span></td>
                            <td class="px-4 py-3"><div class="flex items-center gap-3 whitespace-nowrap"><a href="{{ route('admin.aturan-reward.index', ['edit' => $item->id_aturan]) }}" class="font-semibold text-green-700 hover:underline">Edit</a><form method="POST" action="{{ route('admin.aturan-reward.toggle', $item) }}">@csrf @method('PATCH')<button class="font-semibold {{ $item->status === 'aktif' ? 'text-red-600' : 'text-green-700' }}">{{ $item->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-14 text-center text-sm text-[#6B7A72]">Belum ada aturan reward. Tambahkan aturan pertama untuk mulai mengatur poin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($formAktif)
        @php $aturanForm = $terpilih; @endphp
        <section class="rounded-2xl border border-[#E4EBE7] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="text-base font-bold">{{ $modeTambah ? 'Tambah Aturan' : 'Edit Aturan: '.$aturanForm->kode_aturan }}</h2><a href="{{ route('admin.aturan-reward.index') }}" class="text-xl leading-none text-[#91A398]" aria-label="Tutup">×</a></div>
            <form method="POST" action="{{ $aturanForm ? route('admin.aturan-reward.update', $aturanForm) : route('admin.aturan-reward.store') }}" class="mt-4 space-y-4">
                @csrf @if ($aturanForm) @method('PUT') @endif
                @if ($errors->any())<div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                <label class="block text-sm font-semibold">Nama Aturan <span class="text-red-600">*</span><input name="nama_aturan" required maxlength="100" value="{{ old('nama_aturan', $aturanForm?->nama_aturan) }}" class="mt-2 h-11 w-full rounded-xl border border-[#D5E2DA] px-3 font-normal outline-none focus:border-green-600"></label>
                <fieldset><legend class="text-sm font-semibold">Jenis Aturan</legend><div class="mt-2 grid grid-cols-2 rounded-xl border border-[#D5E2DA] bg-[#F5F8F6] p-1"><label class="cursor-pointer rounded-lg px-3 py-2 text-center text-sm has-[:checked]:bg-white has-[:checked]:font-semibold has-[:checked]:shadow-sm"><input type="radio" class="sr-only" name="jenis_aturan" value="Penambahan" @checked(old('jenis_aturan', $aturanForm?->jenis_aturan ?? 'Penambahan') === 'Penambahan')>Penambahan</label><label class="cursor-pointer rounded-lg px-3 py-2 text-center text-sm has-[:checked]:bg-white has-[:checked]:font-semibold has-[:checked]:shadow-sm"><input type="radio" class="sr-only" name="jenis_aturan" value="Pengurangan" @checked(old('jenis_aturan', $aturanForm?->jenis_aturan ?? 'Penambahan') === 'Pengurangan')>Pengurangan</label></div></fieldset>
                <label class="block text-sm font-semibold">Kategori <span class="text-[#6B7A72]">(opsional untuk aturan global)</span><select name="id_kategori" class="mt-2 h-11 w-full rounded-xl border border-[#D5E2DA] bg-white px-3 font-normal"><option value="">Semua kategori</option>@foreach ($kategoris as $kategori)<option value="{{ $kategori->id_kategori }}" @selected((string) old('id_kategori', $aturanForm?->id_kategori) === (string) $kategori->id_kategori)>{{ $kategori->nama_kategori }}</option>@endforeach</select></label>
                <fieldset><legend class="text-sm font-semibold">Syarat</legend><div class="mt-2 space-y-2">@foreach ($syaratPilihan as $kodeSyarat => $labelSyarat)<label class="flex items-start gap-2 text-sm"><input type="checkbox" name="syarat[]" value="{{ $kodeSyarat }}" @checked(in_array($kodeSyarat, $syaratAktif, true)) class="mt-0.5 accent-green-600">{{ $labelSyarat }}</label>@endforeach</div></fieldset>
                <div class="grid grid-cols-2 gap-3"><label class="block text-sm font-semibold">Nilai Poin<input type="number" min="1" max="1000000" name="nilai_poin" required value="{{ $nilaiAwal }}" class="mt-2 h-11 w-full rounded-xl border border-[#D5E2DA] px-3 font-normal"></label><label class="block text-sm font-semibold">Satuan<select name="satuan" class="mt-2 h-11 w-full rounded-xl border border-[#D5E2DA] bg-white px-3 font-normal">@foreach (['per item', 'per kg', 'flat', 'pengali'] as $satuan)<option value="{{ $satuan }}" @selected(old('satuan', $aturanForm?->satuan ?? 'per item') === $satuan)>{{ ['flat' => 'poin (flat)', 'pengali' => 'pengali (×)'][$satuan] ?? $satuan }}</option>@endforeach</select></label></div>
                <div class="grid grid-cols-2 gap-3"><label class="block text-sm font-semibold">Batas harian<input type="number" min="0" name="batas_per_hari" value="{{ old('batas_per_hari', $aturanForm?->batas_per_hari) }}" placeholder="Tanpa batas" class="mt-2 h-11 w-full rounded-xl border border-[#D5E2DA] px-3 font-normal"></label><label class="block text-sm font-semibold">Berlaku mulai<input type="date" name="berlaku_mulai" value="{{ old('berlaku_mulai', $aturanForm?->berlaku_mulai?->format('Y-m-d')) }}" class="mt-2 h-11 w-full rounded-xl border border-[#D5E2DA] px-3 font-normal"></label></div>
                <label class="flex items-center justify-between"><span><span class="block text-sm font-semibold">Aturan aktif</span><span class="text-xs text-[#6B7A72]">Digunakan untuk perhitungan poin</span></span><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $aturanForm?->status === 'aktif' || ! $aturanForm)) class="h-5 w-5 accent-green-600"></label>
                <div class="flex justify-end gap-3 border-t border-[#EEF3F0] pt-4"><a href="{{ route('admin.aturan-reward.index') }}" class="rounded-xl border border-[#D5E2DA] px-4 py-2.5 text-sm font-semibold">Batal</a><button class="rounded-xl bg-[#16A34A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#15803D]">Simpan Aturan</button></div>
            </form>
        </section>
    @endif
</div>
@endsection
