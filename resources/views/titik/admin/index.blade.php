@extends('titik._layout')

@section('title', 'Kelola Lokasi Tempat Sampah')

@section('content')
    <div class="head">
        <div>
            <h1>Kelola Lokasi Tempat Sampah</h1>
            <p class="muted">Tambah titik baru, ubah informasi dan kategori yang diterima, atau nonaktifkan lokasi yang sudah tidak tersedia.</p>
        </div>
    </div>

    <div class="split {{ isset($titik) ? 'with-panel' : '' }}">
        <section>
            <form method="GET" action="{{ route('titik.admin.index') }}" class="filters">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama lokasi...">
                <select name="area">
                    <option value="">Area: Semua</option>
                    @foreach ($areas as $a)
                        <option value="{{ $a }}" @selected(request('area') === $a)>{{ $a }}</option>
                    @endforeach
                </select>
                <select name="status">
                    <option value="">Status: Semua</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </select>
                <button type="submit" class="btn btn-light">Terapkan</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Lokasi</th>
                        <th>Kategori</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $t)
                        <tr class="{{ isset($titik) && $titik->id === $t->id ? 'pilih' : '' }}">
                            <td><strong>{{ $t->kode_titik }}</strong></td>
                            <td>
                                {{ $t->nama_lokasi }}<br>
                                <span class="muted">{{ $t->keterangan ?: $t->area }}</span>
                            </td>
                            <td>
                                @foreach ($t->kategori as $k)
                                    <span class="dot" style="background: {{ $k->warna_label }}" title="{{ $k->nama_kategori }}"></span>
                                @endforeach
                            </td>
                            <td><span class="badge {{ $t->status }}">{{ $t->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Tidak ada lokasi yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p class="muted">
                @foreach ($kategoris as $k)
                    <span class="dot" style="background: {{ $k->warna_label }}"></span>{{ $k->nama_kategori }}&nbsp;&nbsp;
                @endforeach
                · Lokasi yang dinonaktifkan tidak tampil di peta pengguna, tetapi datanya tetap tersimpan.
            </p>
        </section>

        @isset($titik)
            @include('titik.admin._panel')
        @endisset
    </div>
@endsection
