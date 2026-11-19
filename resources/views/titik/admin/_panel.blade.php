@php
    $aktif = old() ? (bool) old('aktif') : ($titik->exists ? $titik->status === 'aktif' : true);
    $dipilih = collect(old('kategori', $titik->exists ? $titik->kategori->pluck('id_kategori')->all() : []));
@endphp

<aside class="panel">
    <div class="panel-head">
        <h2>{{ $titik->exists ? 'Edit Lokasi: ' . $titik->kode_titik : 'Tambah Lokasi' }}</h2>
        <a href="{{ route('admin.lokasi.index', request()->except(['edit', 'tambah'])) }}" title="Tutup">&times;</a>
    </div>

    <form method="POST" action="{{ $titik->exists ? route('admin.lokasi.update', $titik) : route('admin.lokasi.store') }}">
        @csrf
        @if ($titik->exists)
            @method('PUT')
        @endif

        <label for="nama_lokasi">Nama lokasi *</label>
        <input id="nama_lokasi" name="nama_lokasi" type="text" maxlength="100" required value="{{ old('nama_lokasi', $titik->nama_lokasi) }}">

        <div class="dua">
            <div>
                <label for="kode_titik">Kode *</label>
                <input id="kode_titik" name="kode_titik" type="text" maxlength="10" required value="{{ old('kode_titik', $titik->kode_titik) }}">
            </div>
            <div>
                <label for="area">Area *</label>
                <input id="area" name="area" type="text" maxlength="50" required list="daftar-area" value="{{ old('area', $titik->area) }}">
                <datalist id="daftar-area">
                    @foreach ($areas as $a)
                        <option value="{{ $a }}">
                    @endforeach
                </datalist>
            </div>
        </div>

        <label for="keterangan">Keterangan lokasi</label>
        <input id="keterangan" name="keterangan" type="text" maxlength="255" value="{{ old('keterangan', $titik->keterangan) }}">

        <label>Titik di peta</label>
        <div id="peta-mini"></div>
        <span class="muted">Klik peta atau geser penanda untuk menentukan titik.</span>

        <div class="dua">
            <div>
                <label for="latitude">Latitude *</label>
                <input id="latitude" name="latitude" type="number" step="any" required value="{{ old('latitude', $titik->latitude) }}">
            </div>
            <div>
                <label for="longitude">Longitude *</label>
                <input id="longitude" name="longitude" type="number" step="any" required value="{{ old('longitude', $titik->longitude) }}">
            </div>
        </div>

        <label>Kategori diterima *</label>
        <div>
            @foreach ($kategoris as $k)
                <label class="chip">
                    <input type="checkbox" name="kategori[]" value="{{ $k->id_kategori }}" @checked($dipilih->contains($k->id_kategori))>
                    <span class="dot" style="background: {{ $k->warna_label }}"></span>{{ $k->nama_kategori }}
                </label>
            @endforeach
        </div>

        <label for="fasilitas">Fasilitas</label>
        <input id="fasilitas" name="fasilitas" type="text" maxlength="255" placeholder="Tong terpisah, area beratap, dekat wastafel" value="{{ old('fasilitas', $titik->fasilitas) }}">

        <label for="kode_perangkat">Kode perangkat</label>
        <input id="kode_perangkat" name="kode_perangkat" type="text" maxlength="30" placeholder="Contoh: PRK-A2 (dipakai PBI-13)" value="{{ old('kode_perangkat', $titik->kode_perangkat) }}">

        <label class="cek">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" value="1" @checked($aktif)>
            Lokasi aktif · tampil di peta pengguna
        </label>

        <div class="aksi-form">
            <a href="{{ route('admin.lokasi.index', request()->except(['edit', 'tambah'])) }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn">Simpan Lokasi</button>
        </div>
    </form>
</aside>

@push('scripts')
<script>
    const semua = @json($titikPeta);
    const sedangDiubah = @json($titik->exists ? $titik->id_lokasi : null);

    const latEl = document.getElementById('latitude');
    const lngEl = document.getElementById('longitude');

    const peta = L.map('peta-mini').setView([-6.9733, 107.6303], 16);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(peta);

    // Titik lain ditampilkan kecil sebagai petunjuk posisi.
    semua.filter(t => t.id !== sedangDiubah).forEach(t => {
        L.circleMarker([t.lat, t.lng], { radius: 5, color: t.aktif ? '#16a34a' : '#9ca3af', fillOpacity: 0.7 }).addTo(peta);
    });

    let penanda = null;

    function isi(lat, lng) {
        latEl.value = lat.toFixed(6);
        lngEl.value = lng.toFixed(6);
    }

    function taruh(lat, lng) {
        if (penanda) {
            penanda.setLatLng([lat, lng]);
            return;
        }
        penanda = L.marker([lat, lng], { draggable: true }).addTo(peta);
        penanda.on('dragend', () => {
            const p = penanda.getLatLng();
            isi(p.lat, p.lng);
        });
    }

    function dariInput() {
        const a = parseFloat(latEl.value);
        const b = parseFloat(lngEl.value);
        if (!isNaN(a) && !isNaN(b)) {
            taruh(a, b);
            peta.setView([a, b], 17);
        }
    }

    peta.on('click', e => {
        taruh(e.latlng.lat, e.latlng.lng);
        isi(e.latlng.lat, e.latlng.lng);
    });
    latEl.addEventListener('change', dariInput);
    lngEl.addEventListener('change', dariInput);

    dariInput();
    setTimeout(() => peta.invalidateSize(), 0);
</script>
@endpush
