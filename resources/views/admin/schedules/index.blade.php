<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Pengangkutan | PilahKu</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17221d;background:#f5f7f5;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0}button,input,select,textarea{font:inherit}button{cursor:pointer}
        .shell{min-height:100vh;display:grid;grid-template-columns:244px minmax(0,1fr)}
        .sidebar{background:#173e32;color:#e4f0e9;padding:26px 18px;display:flex;flex-direction:column;gap:32px}
        .brand{display:flex;align-items:center;gap:11px;font-size:19px;font-weight:750;color:#fff;padding:0 10px}
        .brand-mark{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#d6f1dc;color:#17603f;font-weight:900}
        .nav-label{font-size:10px;letter-spacing:.13em;text-transform:uppercase;color:#9eb8aa;margin:0 10px 10px}
        .nav-link{display:block;color:#e4f0e9;text-decoration:none;padding:11px 12px;border-radius:10px;font-size:13px}
        .nav-link.active{background:#245844;color:white;font-weight:650}
        .sidebar-note{margin-top:auto;border:1px solid #47705e;border-radius:13px;padding:14px;color:#c5d9cd;font-size:12px;line-height:1.55}
        main{min-width:0;padding:36px clamp(18px,4vw,54px) 48px}
        .topline{font-size:12px;color:#738078;margin-bottom:12px}.heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:28px}
        h1{font-size:clamp(23px,3vw,30px);letter-spacing:-.04em;margin:0 0 7px}.subtitle{color:#758079;font-size:13px;margin:0}
        .primary,.secondary,.quiet{border:0;border-radius:9px;padding:11px 15px;font-weight:650;font-size:13px}
        .primary{background:#20764b;color:white;box-shadow:0 4px 10px #20764b20}.primary:hover{background:#17643e}
        .secondary{background:#edf3ef;color:#335743}.quiet{background:transparent;color:#607068;padding:8px 10px}
        .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}
        .tabs{display:flex;gap:5px;border-bottom:1px solid #dfe6e1}
        .tab{padding:11px 14px;text-decoration:none;color:#718078;font-size:13px;font-weight:600;border-bottom:2px solid transparent;margin-bottom:-1px}
        .tab.active{color:#20764b;border-color:#20764b}
        .count{display:inline-grid;place-items:center;min-width:21px;height:21px;padding:0 6px;border-radius:20px;background:#edf2ee;font-size:11px;margin-left:4px}
        .search{display:flex;gap:7px}.search input{width:min(245px,45vw);border:1px solid #dce4de;border-radius:9px;padding:9px 11px;background:white}
        .notice{border-radius:10px;padding:12px 15px;margin-bottom:18px;font-size:13px}.notice.success{background:#e8f6ec;color:#23613b}.notice.error{background:#fff0ed;color:#9f3b2d}
        .layout{display:grid;grid-template-columns:minmax(0,1fr) 350px;gap:18px;align-items:start}
        .card{background:white;border:1px solid #e4e9e5;border-radius:14px;box-shadow:0 3px 14px #253c2c08}
        .table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:670px}th{text-align:left;color:#839087;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;padding:15px 17px;border-bottom:1px solid #edf0ed}
        td{padding:15px 17px;border-bottom:1px solid #f0f2f0;font-size:12px;vertical-align:middle}tbody tr:last-child td{border-bottom:0}
        .category{display:flex;align-items:center;gap:9px;font-weight:650}.dot{width:9px;height:9px;border-radius:50%;background:#738078}.dot.organik{background:#2f855a}.dot.plastik{background:#3182ce}.dot.kertas{background:#d69e2e}
        .muted{color:#79847d}.pill{display:inline-flex;align-items:center;border-radius:30px;padding:5px 9px;background:#e9f6ec;color:#36744b;font-size:10px;font-weight:700}
        .pill.off{background:#f0f1f0;color:#6b756e}.actions{display:flex;gap:4px;white-space:nowrap}.actions form{display:inline}
        .text-action{border:0;background:none;color:#347354;font-size:11px;font-weight:650;padding:5px}.text-action.danger{color:#a4493b}
        .empty{padding:48px 20px;text-align:center;color:#839087;font-size:13px}
        .pagination{padding:12px 17px;border-top:1px solid #edf0ed;font-size:12px}
        .panel{padding:20px;position:sticky;top:18px}.panel-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:17px}
        h2{font-size:16px;margin:0 0 4px;letter-spacing:-.02em}.panel-copy{font-size:11px;color:#839087;margin:0}
        .field{margin-bottom:13px}.field label,.field-label{display:block;font-size:11px;font-weight:700;color:#45534a;margin-bottom:6px}
        .field input:not([type=checkbox]),.field select,.field textarea{width:100%;border:1px solid #dce4de;border-radius:8px;padding:9px 10px;background:white;color:#26342b;font-size:12px}
        .field select[multiple]{height:84px}.field textarea{resize:vertical;min-height:58px}.field-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}
        .radio-row{display:flex;gap:5px}.radio-option{flex:1}.radio-option input{position:absolute;opacity:0}.radio-option span{display:block;text-align:center;border:1px solid #dce4de;border-radius:8px;padding:9px 4px;font-size:10px;color:#657269}
        .radio-option input:checked+span{border-color:#278051;background:#eef8f1;color:#20764b;font-weight:700}
        .checkbox{display:flex;align-items:center;gap:8px;font-size:11px;color:#526158;margin:3px 0 13px}.checkbox input{accent-color:#20764b}
        .hint{font-size:10px;color:#849087;margin:5px 0 0}.error-text{font-size:10px;color:#b34837;margin-top:5px}
        .form-actions{display:flex;justify-content:flex-end;gap:8px;padding-top:6px}.form-actions .primary{min-width:138px}
        .hidden{display:none!important}.mobile-title{display:none}
        @media(max-width:1100px){.layout{grid-template-columns:minmax(0,1fr)}.panel{position:static}.panel[hidden]{display:none!important}}
        @media(max-width:700px){.shell{grid-template-columns:1fr}.sidebar{padding:12px 16px;flex-direction:row;align-items:center;justify-content:space-between;gap:10px}.sidebar .nav-label,.sidebar-note{display:none}.brand{font-size:17px}.nav-link{padding:9px;font-size:11px}.sidebar nav{display:flex}.sidebar nav .nav-link:not(.active){display:none}main{padding:23px 15px 36px}.heading{display:block}.heading .primary{margin-top:16px;width:100%}.toolbar{align-items:flex-start;flex-direction:column}.search,.search input{width:100%}.panel{padding:16px}.field-grid{gap:7px}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">P</span> PilahKu</div>
        <nav>
            <p class="nav-label">Manajemen</p>
            <a class="nav-link active" href="{{ route('admin.schedules.index') }}">Jadwal Pengangkutan</a>
        </nav>
        <div class="sidebar-note">Kelola jadwal pengangkutan agar informasi pemilahan selalu konsisten.</div>
    </aside>
    <main>
        <div class="topline">Admin <span aria-hidden="true">/</span> Jadwal Pengangkutan</div>
        <header class="heading">
            <div>
                <h1>Jadwal Pengangkutan</h1>
                <p class="subtitle">Atur jadwal pengangkutan sampah kampus. Jadwal yang tidak berlaku dinonaktifkan, bukan dihapus.</p>
            </div>
            <button type="button" class="primary" id="add-schedule">＋ Tambah Jadwal</button>
        </header>

        @if (session('success'))
            <div class="notice success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->has('status'))
            <div class="notice error" role="alert">{{ $errors->first('status') }}</div>
        @endif

        <div class="toolbar">
            <div class="tabs">
                <a class="tab {{ $status === 'aktif' ? 'active' : '' }}" href="{{ route('admin.schedules.index', ['status' => 'aktif']) }}">Aktif <span class="count">{{ $activeCount }}</span></a>
                <a class="tab {{ $status === 'nonaktif' ? 'active' : '' }}" href="{{ route('admin.schedules.index', ['status' => 'nonaktif']) }}">Nonaktif <span class="count">{{ $inactiveCount }}</span></a>
            </div>
            <form class="search" method="GET" action="{{ route('admin.schedules.index') }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kategori atau area..." aria-label="Cari kategori atau area">
                <button class="secondary" type="submit">Cari</button>
            </form>
        </div>

        @if ($errors->has('jam_angkut') && !old('_method'))
            <div class="notice error" role="alert">{{ $errors->first('jam_angkut') }}</div>
        @endif

        <div class="layout">
            <section class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr><th>Kategori</th><th>Area</th><th>Pola</th><th>Jam angkut</th><th>Status</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($schedules as $schedule)
                            @php
                                $scheduleData = $schedule->only(['id_jadwal', 'id_kategori', 'pola', 'interval_hari', 'hari_ke', 'hari', 'berlaku_mulai', 'berlaku_sampai', 'jam_buang_mulai', 'jam_buang_selesai', 'jam_angkut', 'catatan', 'kirim_pengingat', 'semua_area']);
                                $scheduleData['id_lokasi'] = $schedule->locations->pluck('id_lokasi')->all();
                            @endphp
                            <tr>
                                <td><span class="category"><span class="dot {{ strtolower($schedule->category->nama) }}"></span>{{ $schedule->category->nama }}</span></td>
                                <td>{{ $schedule->semua_area ? 'Semua area' : $schedule->locations->pluck('nama')->join(', ') }}</td>
                                <td>{{ ucfirst($schedule->pola) }}{{ $schedule->pola === 'siklus' ? ' '.$schedule->interval_hari.' hari' : '' }}</td>
                                <td>{{ substr($schedule->jam_angkut, 0, 5) }}</td>
                                <td><span class="pill {{ $schedule->status === 'nonaktif' ? 'off' : '' }}">{{ ucfirst($schedule->status) }}</span></td>
                                <td>
                                    <div class="actions">
                                        <button type="button" class="text-action edit-schedule" data-schedule='@json($scheduleData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'>Edit</button>
                                        <form method="POST" action="{{ route('admin.schedules.status', $schedule) }}" onsubmit="return confirm('Yakin ingin {{ $schedule->status === 'aktif' ? 'menonaktifkan' : 'mengaktifkan' }} jadwal ini?')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $schedule->status === 'aktif' ? 'nonaktif' : 'aktif' }}">
                                            <button class="text-action {{ $schedule->status === 'aktif' ? 'danger' : '' }}" type="submit">{{ $schedule->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty">Belum ada jadwal {{ $status }}.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($schedules->hasPages())
                    <div class="pagination">{{ $schedules->links() }}</div>
                @endif
            </section>

            <aside class="card panel" id="schedule-panel" @if (!$errors->any()) hidden @endif>
                <div class="panel-head">
                    <div><h2 id="form-title">Tambah Jadwal</h2><p class="panel-copy">Lengkapi informasi jadwal pengangkutan.</p></div>
                    <button class="quiet" type="button" id="close-panel" aria-label="Tutup form">✕</button>
                </div>
                <form id="schedule-form" method="POST" action="{{ route('admin.schedules.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    <input type="hidden" name="edit_id" id="edit-id" value="">
                    <div class="field">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id_kategori }}">{{ $category->nama }}</option>
                            @endforeach
                        </select>
                        @error('id_kategori')<div class="error-text">{{ $message }}</div>@enderror
                    </div>
                    <label class="checkbox"><input type="checkbox" name="semua_area" value="1" id="semua-area"> Semua area</label>
                    <div class="field" id="location-field">
                        <label for="id_lokasi">Area / titik (pilih satu atau lebih)</label>
                        <select id="id_lokasi" name="id_lokasi[]" multiple size="3">
                            @foreach ($locations as $location)
                                <option value="{{ $location->id_lokasi }}">{{ $location->nama }}</option>
                            @endforeach
                        </select>
                        @error('id_lokasi')<div class="error-text">{{ $message }}</div>@enderror
                        @error('id_lokasi.*')<div class="error-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <span class="field-label">Pola pengulangan</span>
                        <div class="radio-row">
                            @foreach (['siklus' => 'Siklus', 'mingguan' => 'Mingguan', 'sekali' => 'Sekali'] as $value => $label)
                                <label class="radio-option"><input type="radio" name="pola" value="{{ $value }}" {{ $value === 'mingguan' ? 'checked' : '' }}><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field-grid" id="cycle-fields">
                        <div class="field"><label for="interval_hari">Siklus (hari)</label><input id="interval_hari" name="interval_hari" type="number" min="1" max="365" placeholder="Contoh: 3"></div>
                        <div class="field"><label for="hari_ke">Hari ke-</label><input id="hari_ke" name="hari_ke" type="number" min="1" placeholder="Contoh: 1"></div>
                    </div>
                    <div class="field hidden" id="weekday-field">
                        <label for="hari">Hari</label>
                        <select id="hari" name="hari">
                            <option value="">Pilih hari</option>
                            @foreach (['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-grid">
                        <div class="field"><label for="berlaku_mulai">Mulai berlaku</label><input id="berlaku_mulai" name="berlaku_mulai" type="date" required></div>
                        <div class="field"><label for="berlaku_sampai">Berlaku sampai</label><input id="berlaku_sampai" name="berlaku_sampai" type="date"></div>
                    </div>
                    <div class="field-grid">
                        <div class="field"><label for="jam_buang_mulai">Buang mulai</label><input id="jam_buang_mulai" name="jam_buang_mulai" type="time" required></div>
                        <div class="field"><label for="jam_buang_selesai">Buang selesai</label><input id="jam_buang_selesai" name="jam_buang_selesai" type="time" required></div>
                    </div>
                    <div class="field"><label for="jam_angkut">Jam angkut</label><input id="jam_angkut" name="jam_angkut" type="time" required></div>
                    <div class="field"><label for="catatan">Catatan <span class="muted">(opsional)</span></label><textarea id="catatan" name="catatan" maxlength="255" placeholder="Maksimal 255 karakter"></textarea></div>
                    <label class="checkbox"><input type="checkbox" name="kirim_pengingat" value="1" id="kirim-pengingat" checked> Kirim pengingat ke pengguna</label>
                    @foreach (['hari', 'interval_hari', 'hari_ke', 'berlaku_mulai', 'berlaku_sampai', 'jam_buang_mulai', 'jam_buang_selesai', 'jam_angkut', 'catatan'] as $field)
                        @error($field)<div class="error-text">{{ $message }}</div>@enderror
                    @endforeach
                    <div class="form-actions"><button type="button" class="secondary" id="cancel-form">Batal</button><button type="submit" class="primary">Simpan Jadwal</button></div>
                </form>
            </aside>
        </div>
    </main>
</div>
<script>
(() => {
    const panel = document.getElementById('schedule-panel');
    const form = document.getElementById('schedule-form');
    const method = document.getElementById('form-method');
    const title = document.getElementById('form-title');
    const patternInputs = [...document.querySelectorAll('input[name="pola"]')];
    const cycleFields = document.getElementById('cycle-fields');
    const weekdayField = document.getElementById('weekday-field');
    const updatePattern = (pattern) => {
        cycleFields.classList.toggle('hidden', pattern !== 'siklus');
        weekdayField.classList.toggle('hidden', pattern !== 'mingguan');
        document.getElementById('interval_hari').required = pattern === 'siklus';
        document.getElementById('hari_ke').required = pattern === 'siklus';
        document.getElementById('hari').required = pattern === 'mingguan';
    };
    const openForm = (schedule = null) => {
        form.reset();
        form.action = schedule
            ? `{{ url('/admin/jadwal-pengangkutan') }}/${schedule.id_jadwal}`
            : `{{ route('admin.schedules.store') }}`;
        method.value = schedule ? 'PUT' : 'POST';
        document.getElementById('edit-id').value = schedule ? schedule.id_jadwal : '';
        title.textContent = schedule ? 'Edit Jadwal' : 'Tambah Jadwal';
        document.getElementById('kirim-pengingat').checked = schedule ? Boolean(schedule.kirim_pengingat) : true;
        if (schedule) {
            for (const field of ['id_kategori', 'interval_hari', 'hari_ke', 'hari', 'berlaku_mulai', 'berlaku_sampai', 'jam_buang_mulai', 'jam_buang_selesai', 'jam_angkut', 'catatan']) {
                if (schedule[field] !== null && schedule[field] !== undefined) {
                    const input = document.getElementById(field);
                    if (input) input.value = schedule[field];
                }
            }
            const pattern = document.querySelector(`input[name="pola"][value="${schedule.pola}"]`);
            if (pattern) pattern.checked = true;
            document.getElementById('semua-area').checked = Boolean(schedule.semua_area);
            for (const option of document.getElementById('id_lokasi').options) {
                option.selected = schedule.id_lokasi.includes(Number(option.value));
            }
        } else {
            document.querySelector('input[name="pola"][value="mingguan"]').checked = true;
        }
        updatePattern(form.querySelector('input[name="pola"]:checked').value);
        document.getElementById('location-field').classList.toggle('hidden', document.getElementById('semua-area').checked);
        panel.hidden = false;
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    const closeForm = () => { panel.hidden = true; };
    document.getElementById('add-schedule').addEventListener('click', () => openForm());
    document.getElementById('close-panel').addEventListener('click', closeForm);
    document.getElementById('cancel-form').addEventListener('click', closeForm);
    document.querySelectorAll('.edit-schedule').forEach((button) => {
        button.addEventListener('click', () => openForm(JSON.parse(button.dataset.schedule)));
    });
    patternInputs.forEach((input) => input.addEventListener('change', () => updatePattern(input.value)));
    document.getElementById('semua-area').addEventListener('change', (event) => {
        document.getElementById('location-field').classList.toggle('hidden', event.target.checked);
        document.getElementById('id_lokasi').setCustomValidity('');
    });
    document.getElementById('id_lokasi').addEventListener('change', () => {
        document.getElementById('id_lokasi').setCustomValidity('');
    });
    form.addEventListener('submit', (event) => {
        const start = document.getElementById('jam_buang_mulai').value;
        const end = document.getElementById('jam_buang_selesai').value;
        const haul = document.getElementById('jam_angkut').value;
        const dateStart = document.getElementById('berlaku_mulai').value;
        const dateEnd = document.getElementById('berlaku_sampai').value;
        const locationSelect = document.getElementById('id_lokasi');
        if (!document.getElementById('semua-area').checked && ![...locationSelect.selectedOptions].length) {
            event.preventDefault();
            locationSelect.setCustomValidity('Pilih setidaknya satu lokasi atau pilih Semua area.');
            locationSelect.reportValidity();
            return;
        }
        locationSelect.setCustomValidity('');
        if (end <= start || haul < end || (dateEnd && dateEnd < dateStart)) {
            event.preventDefault();
            alert('Periksa kembali rentang jam buang, jam angkut, dan periode berlaku.');
        }
    });
    updatePattern('mingguan');
    @if ($errors->any())
        const editButton = [...document.querySelectorAll('.edit-schedule')].find((button) => {
            return JSON.parse(button.dataset.schedule).id_jadwal === Number(@json(session('edit_schedule') ?? old('edit_id')));
        });
        openForm(editButton ? JSON.parse(editButton.dataset.schedule) : null);
        const oldInput = @json(old());
        for (const [name, value] of Object.entries(oldInput)) {
            if (name === '_token' || name === '_method' || name === 'id_lokasi') continue;
            const input = form.elements.namedItem(name);
            if (input && input.type !== 'radio' && input.type !== 'checkbox') input.value = value;
        }
        if (oldInput.id_lokasi) {
            for (const option of document.getElementById('id_lokasi').options) {
                option.selected = oldInput.id_lokasi.map(Number).includes(Number(option.value));
            }
        }
        if (oldInput.semua_area) document.getElementById('semua-area').checked = true;
        if (oldInput.kirim_pengingat === undefined) document.getElementById('kirim-pengingat').checked = false;
        if (oldInput.pola) {
            document.querySelector(`input[name="pola"][value="${oldInput.pola}"]`).checked = true;
            updatePattern(oldInput.pola);
        }
        document.getElementById('location-field').classList.toggle('hidden', document.getElementById('semua-area').checked);
    @endif
})();
</script>
</body>
</html>
