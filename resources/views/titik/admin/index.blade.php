@extends('titik._layout')

@section('title', 'Kelola Lokasi Tempat Sampah')

@section('content')
    <div class="head">
        <div>
            <h1>Kelola Lokasi Tempat Sampah</h1>
            <p class="muted">Tambah titik baru, ubah informasi dan kategori yang diterima, atau nonaktifkan lokasi yang sudah tidak tersedia.</p>
        </div>
        <a href="{{ route('titik.admin.index', ['tambah' => 1] + request()->except(['edit', 'tambah'])) }}" class="btn">+ Tambah Lokasi</a>
    </div>

    <div class="split {{ isset($titik) ? 'with-panel' : '' }}">
        <section>
            <div id="peta-daftar"></div>
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
                        <th>Aksi</th>
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
                                <td class="aksi">
                                <a class="lnk" href="{{ route('titik.admin.index', ['edit' => $t->id] + request()->except(['edit', 'tambah'])) }}">Edit</a>
                                 <form method="POST" action="{{ route('titik.admin.toggle', $t) }}"
                                      @if ($t->status === 'aktif') onsubmit="return confirm('Nonaktifkan lokasi ini? Titik tidak akan tampil di peta pengguna.')" @endif>
                                    <button type="submit" class="lnk {{ $t->status === 'aktif' ? 'merah' : '' }}">{{ $t->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Tidak ada lokasi yang cocok.</td></tr>
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
@push('scripts')
<script>
    (function () {
        const titik = @json($titikPeta);
        const urlDaftar = @json(route('titik.admin.index'));

        const peta = L.map('peta-daftar').setView([-6.9733, 107.6303], 16);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(peta);

        titik.forEach(t => {
            const label = document.createElement('span');
            label.textContent = t.kode + ' · ' + t.nama;

            L.circleMarker([t.lat, t.lng], {
                radius: 9,
                color: t.aktif ? '#16a34a' : '#9ca3af',
                fillOpacity: 0.8
            }).addTo(peta)
              .bindTooltip(label)
              .on('click', () => { window.location = urlDaftar + '?edit=' + t.id; });
        });

        if (titik.length) {
            peta.fitBounds(titik.map(t => [t.lat, t.lng]), { padding: [30, 30], maxZoom: 18 });
        }
    })();
</script>
@endpush
