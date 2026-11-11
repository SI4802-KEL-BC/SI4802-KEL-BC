<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Lokasi | PilahKu</title>
    <style>
        :root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#24342b;background:#f5f7f5}
        *{box-sizing:border-box}body{margin:0}main{max-width:1180px;margin:0 auto;padding:32px 18px}
        h1{margin:0 0 6px;font-size:28px}h2{font-size:18px;margin:0 0 16px}
        .muted{color:#718078;font-size:13px;margin:0 0 24px}.card{background:white;border:1px solid #e1e8e2;border-radius:12px;padding:20px;margin-bottom:20px}
        .notice{padding:12px 14px;border-radius:8px;margin-bottom:14px;background:#e8f6ec;color:#23613b}.errors{background:#fff0ed;color:#9f3b2d}
        .fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.field label{display:block;font-size:12px;font-weight:650;margin-bottom:5px}
        input,select,button{font:inherit}input,select{width:100%;border:1px solid #d5ded7;border-radius:7px;padding:9px;background:#fff}
        select[multiple]{min-height:84px}.help{font-size:11px;color:#718078;margin:5px 0 0}.primary,.secondary{border:0;border-radius:7px;padding:9px 13px;font-size:12px;font-weight:650;cursor:pointer}
        .primary{background:#20764b;color:white}.secondary{background:#edf3ef;color:#335743}.form-actions{margin-top:14px}
        .table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:880px}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #edf0ed;vertical-align:top}
        th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#718078}td{font-size:12px}.row-form{min-width:650px}.row-form .fields{grid-template-columns:repeat(3,minmax(120px,1fr))}
        .actions{display:flex;gap:8px;align-items:center;margin-top:10px}.status{font-weight:700}.status.off{color:#78817a}
        @media(max-width:700px){main{padding:22px 12px}.fields{grid-template-columns:1fr 1fr}.row-form .fields{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<main>
    <h1>Kelola Lokasi Tempat Sampah</h1>
    <p class="muted">Tambah titik lokasi, atur kategori yang diterima, dan nonaktifkan titik yang tidak digunakan.</p>

    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice errors" role="alert">
            <strong>Data belum tersimpan:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="card">
        <h2>Tambah lokasi</h2>
        <form method="post" action="{{ route('admin.lokasi.store') }}">
            @csrf
            <div class="fields">
                <div class="field">
                    <label for="nama_lokasi">Nama lokasi</label>
                    <input id="nama_lokasi" name="nama_lokasi" value="{{ old('nama_lokasi') }}" maxlength="100" required>
                </div>
                <div class="field">
                    <label for="kode_titik">Kode titik</label>
                    <input id="kode_titik" name="kode_titik" value="{{ old('kode_titik') }}" maxlength="10" required>
                </div>
                <div class="field">
                    <label for="area">Area</label>
                    <input id="area" name="area" value="{{ old('area') }}" maxlength="50" required>
                </div>
                <div class="field">
                    <label for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="0.000001" min="-6.990" max="-6.960" value="{{ old('latitude', '-6.975000') }}" required>
                </div>
                <div class="field">
                    <label for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="0.000001" min="107.615" max="107.645" value="{{ old('longitude', '107.630000') }}" required>
                </div>
                <div class="field">
                    <label for="kategori">Kategori yang diterima</label>
                    <select id="kategori" name="kategori[]" multiple required>
                        @foreach ($kategoris as $kategori)
                            <option value="{{ $kategori->id_kategori }}" @selected(in_array($kategori->id_kategori, old('kategori', [])))>{{ $kategori->nama_kategori }}</option>
                        @endforeach
                    </select>
                    <p class="help">Pilih satu atau beberapa kategori.</p>
                </div>
            </div>
            <div class="form-actions"><button class="primary" type="submit">Tambah lokasi</button></div>
        </form>
    </section>

    <section class="card">
        <h2>Daftar lokasi</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Lokasi</th><th>Status</th><th>Kategori dan koordinat</th><th>Pengaturan</th></tr></thead>
                <tbody>
                @forelse ($lokasis as $lokasi)
                    <tr>
                        <td>
                            <strong>{{ $lokasi->nama_lokasi }}</strong><br>
                            <span class="muted">{{ $lokasi->kode_titik }} · {{ $lokasi->area }}</span>
                        </td>
                        <td><span class="status {{ $lokasi->status === 'aktif' ? '' : 'off' }}">{{ ucfirst($lokasi->status) }}</span></td>
                        <td>
                            {{ $lokasi->kategori->pluck('nama_kategori')->implode(', ') }}<br>
                            <span class="muted">{{ $lokasi->latitude }}, {{ $lokasi->longitude }}</span>
                        </td>
                        <td>
                            <form class="row-form" method="post" action="{{ route('admin.lokasi.update', $lokasi) }}">
                                @csrf
                                @method('put')
                                <div class="fields">
                                    <div class="field"><label>Nama lokasi</label><input name="nama_lokasi" value="{{ $lokasi->nama_lokasi }}" maxlength="100" required></div>
                                    <div class="field"><label>Kode titik</label><input name="kode_titik" value="{{ $lokasi->kode_titik }}" maxlength="10" required></div>
                                    <div class="field"><label>Area</label><input name="area" value="{{ $lokasi->area }}" maxlength="50" required></div>
                                    <div class="field"><label>Latitude</label><input name="latitude" type="number" step="0.000001" min="-6.990" max="-6.960" value="{{ $lokasi->latitude }}" required></div>
                                    <div class="field"><label>Longitude</label><input name="longitude" type="number" step="0.000001" min="107.615" max="107.645" value="{{ $lokasi->longitude }}" required></div>
                                    <div class="field">
                                        <label>Kategori yang diterima</label>
                                        <select name="kategori[]" multiple required>
                                            @foreach ($kategoris as $kategori)
                                                <option value="{{ $kategori->id_kategori }}" @selected($lokasi->kategori->contains('id_kategori', $kategori->id_kategori))>{{ $kategori->nama_kategori }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="actions">
                                    <button class="primary" type="submit">Simpan perubahan</button>
                                    <span class="muted">{{ $lokasi->latitude }}, {{ $lokasi->longitude }}</span>
                                </div>
                            </form>
                            <form class="actions" method="post" action="{{ route('admin.lokasi.toggle', $lokasi) }}">
                                @csrf
                                <button class="secondary" type="submit">{{ $lokasi->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Belum ada lokasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
