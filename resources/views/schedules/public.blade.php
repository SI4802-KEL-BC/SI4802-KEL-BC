<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Pengangkutan | PilahKu</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17221d;background:#f5f7f5;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0}a{color:inherit}button,input,select{font:inherit}
        .shell{min-height:100vh;display:grid;grid-template-columns:244px minmax(0,1fr)}
        .sidebar{background:#173e32;color:#e4f0e9;padding:26px 18px;display:flex;flex-direction:column;gap:32px}
        .brand{display:flex;align-items:center;gap:11px;font-size:19px;font-weight:750;color:#fff;padding:0 10px;text-decoration:none}
        .brand-mark{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#d6f1dc;color:#17603f;font-weight:900}
        .nav-label{font-size:10px;letter-spacing:.13em;text-transform:uppercase;color:#9eb8aa;margin:0 10px 10px}
        .nav-link{display:block;color:#e4f0e9;text-decoration:none;padding:11px 12px;border-radius:10px;font-size:13px}
        .nav-link.active{background:#245844;color:white;font-weight:650}
        .sidebar-note{margin-top:auto;border:1px solid #47705e;border-radius:13px;padding:14px;color:#c5d9cd;font-size:12px;line-height:1.55}
        main{min-width:0;padding:36px clamp(18px,4vw,54px) 48px}
        .topline{font-size:12px;color:#738078;margin-bottom:12px}.heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:23px}
        h1{font-size:clamp(23px,3vw,30px);letter-spacing:-.04em;margin:0 0 7px}.subtitle{color:#758079;font-size:13px;margin:0}
        .primary,.secondary{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border:0;border-radius:9px;padding:11px 15px;font-weight:650;font-size:13px}
        .primary{background:#20764b;color:white;box-shadow:0 4px 10px #20764b20}.primary:hover{background:#17643e}
        .secondary{background:#edf3ef;color:#335743}.card{background:white;border:1px solid #e4e9e5;border-radius:14px;box-shadow:0 3px 14px #253c2c08}
        .hero{position:relative;overflow:hidden;background:linear-gradient(115deg,#1d7149,#2f9461);color:white;padding:25px 27px;display:grid;grid-template-columns:minmax(0,1fr) 210px;gap:18px;margin-bottom:21px}
        .hero:after{content:"";position:absolute;width:270px;height:270px;border:1px solid #ffffff20;border-radius:50%;right:105px;top:-180px;box-shadow:0 0 0 30px #ffffff08,0 0 0 60px #ffffff06;pointer-events:none}
        .hero-label{font-size:11px;text-transform:uppercase;letter-spacing:.12em;color:#d0edda;font-weight:700}
        .hero h2{font-size:22px;letter-spacing:-.03em;margin:8px 0}.hero-copy{font-size:13px;color:#e3f2e8;margin:0;line-height:1.55}
        .countdown{position:relative;z-index:1;align-self:center;border:1px solid #ffffff40;background:#ffffff16;border-radius:13px;padding:15px 17px}
        .countdown-label{font-size:10px;color:#e0f0e5}.countdown-value{font-size:25px;font-weight:750;letter-spacing:-.03em;margin-top:5px;font-variant-numeric:tabular-nums}
        .countdown-done{font-size:14px;font-weight:700;margin-top:8px}
        .section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:23px 0 11px}
        h2{font-size:16px;margin:0;letter-spacing:-.02em}.section-note{font-size:11px;color:#849087}
        .week-nav{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:9px}
        .day-card{display:flex;flex-direction:column;align-items:center;gap:7px;min-height:94px;padding:12px 6px;border:1px solid #e4e9e5;border-radius:12px;text-decoration:none;background:#fff;color:#5d6a61;transition:.15s}
        .day-card:hover{border-color:#90bda0;transform:translateY(-1px)}.day-name{font-size:11px;font-weight:700}
        .day-date{font-size:19px;font-weight:750;color:#26382d;line-height:1}.day-haul-time{font-size:9px;color:#65736a;text-align:center;line-height:1.35}
        .day-card.selected{background:#e8f5ec;border-color:#76ae88;color:#246541}.day-card.selected .day-date{color:#17643e}
        .day-card.today:not(.selected){box-shadow:inset 0 0 0 1px #b4cbb9}.today-mark{font-size:8px;color:#2d7950;font-weight:700;min-height:10px}
        .filters{display:flex;align-items:end;gap:10px;flex-wrap:wrap;padding:14px;margin:15px 0}
        .filter-field{display:flex;flex-direction:column;gap:6px;min-width:180px}.filter-field label{font-size:10px;color:#647168;font-weight:700}
        .filter-field input,.filter-field select{border:1px solid #dce4de;border-radius:8px;padding:9px 10px;background:white;color:#26342b;font-size:12px;min-height:38px}
        .filter-actions{display:flex;gap:8px}.clear-link{font-size:12px;text-decoration:none;color:#607068;padding:10px}
        .content-grid{display:grid;grid-template-columns:minmax(0,1fr) 255px;gap:16px;align-items:start}
        .table-card{overflow:hidden}.table-title{padding:17px 18px 13px}.table-title h2{font-size:15px}.table-title p{margin:5px 0 0;font-size:11px;color:#839087}
        .table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:680px}
        th{text-align:left;color:#839087;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;padding:12px 14px;border-bottom:1px solid #edf0ed}
        td{padding:13px 14px;border-bottom:1px solid #f0f2f0;font-size:12px;vertical-align:middle}
        tbody tr:last-child td{border-bottom:0}.category-badge{display:inline-flex;align-items:center;gap:7px;padding:6px 9px;border-radius:30px;font-size:11px;font-weight:700;white-space:nowrap}
        .category-dot{width:8px;height:8px;border-radius:50%;background:#738078}
        .category-organik{background:#e8f5ec;color:#286b42}.category-organik .category-dot{background:#2f855a}
        .category-plastik{background:#eaf2fb;color:#2b5f9a}.category-plastik .category-dot{background:#3182ce}
        .category-kertas{background:#fff7df;color:#8b6817}.category-kertas .category-dot{background:#d69e2e}
        .status{display:inline-flex;padding:6px 9px;border-radius:30px;font-size:10px;font-weight:700;white-space:nowrap}
        .status.scheduled{background:#fff4d8;color:#8c6817}.status.done{background:#e8f5ec;color:#286b42}
        .empty{padding:39px 20px;text-align:center;color:#718078;font-size:13px}.empty strong{display:block;color:#33443a;font-size:14px;margin-bottom:6px}
        .error{background:#fff0ed;border:1px solid #f4d0c9;color:#9f3b2d;padding:11px 14px;border-radius:9px;font-size:12px;margin:0 0 13px}
        .reminder{padding:19px}.reminder-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:11px;background:#e8f5ec;color:#20764b;font-size:17px;margin-bottom:14px}
        .reminder h2{font-size:15px}.reminder p{font-size:11px;color:#77837b;line-height:1.6;margin:8px 0 15px}
        .reminder .primary{width:100%;font-size:11px;padding:10px}.reminder-hint{margin-top:9px!important;font-size:10px!important}
        .footer-note{font-size:10px;color:#89938c;margin-top:17px}
        @media(max-width:1050px){.shell{grid-template-columns:205px minmax(0,1fr)}main{padding:28px 23px}.content-grid{grid-template-columns:minmax(0,1fr)}.reminder{display:grid;grid-template-columns:40px 1fr auto;align-items:center;gap:12px}.reminder-icon{margin:0}.reminder p{margin:4px 0}.reminder .primary{width:auto}.reminder-hint{display:none}}
        @media(max-width:700px){.shell{grid-template-columns:1fr}.sidebar{padding:12px 16px;flex-direction:row;align-items:center;justify-content:space-between;gap:10px}.sidebar .nav-label,.sidebar-note{display:none}.brand{font-size:17px}.nav-link{padding:9px;font-size:11px}.sidebar nav{display:flex}.sidebar nav .nav-link:not(.active){display:none}main{padding:22px 14px 32px}.heading{align-items:flex-start}.heading .primary{font-size:11px;padding:10px}.hero{grid-template-columns:1fr;padding:20px;gap:15px}.hero h2{font-size:20px}.countdown{max-width:none}.countdown-value{font-size:23px}.week-nav{gap:5px}.day-card{min-height:82px;padding:10px 2px;gap:6px}.day-name{font-size:9px}.day-date{font-size:17px}.day-haul-time{font-size:7px}.today-mark{font-size:7px}.filters{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:12px}.filter-field{min-width:0}.filter-field select,.filter-field input{width:100%;font-size:11px}.filter-actions{grid-column:1/-1}.filter-actions .primary{flex:1}.clear-link{text-align:center}.table-card{background:transparent;border:0;box-shadow:none}.table-title{padding:13px 1px}.table-wrap{overflow:visible}table{min-width:0;display:block}thead{display:none}tbody{display:grid;gap:10px}tbody tr{display:grid;grid-template-columns:1fr 1fr;gap:0;background:#fff;border:1px solid #e4e9e5;border-radius:12px;box-shadow:0 3px 14px #253c2c08;overflow:hidden}tbody tr td{display:flex;flex-direction:column;gap:5px;padding:11px 12px;border-bottom:1px solid #f0f2f0;font-size:12px;min-width:0}tbody tr td:before{content:attr(data-label);font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:#89938c;font-weight:700}tbody tr td:last-child{border-bottom:0}.empty-cell{grid-column:1/-1}.empty-cell:before{display:none}.empty{padding:27px 12px}.reminder{grid-template-columns:34px minmax(0,1fr);padding:15px}.reminder .primary{grid-column:1/-1;width:100%}.section-head{margin-top:20px}.section-note{font-size:9px}.footer-note{line-height:1.5}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('schedules.public') }}"><span class="brand-mark">P</span> PilahKu</a>
        <nav>
            <p class="nav-label">Informasi</p>
            <a class="nav-link active" href="{{ route('schedules.public') }}">Jadwal Pengangkutan</a>
        </nav>
        <div class="sidebar-note">Temukan waktu dan lokasi pengangkutan sampah kampus agar kamu bisa memilah dengan tepat.</div>
    </aside>
    <main>
        <div class="topline">Informasi <span aria-hidden="true">/</span> Jadwal Pengangkutan</div>
        <header class="heading">
            <div>
                <h1>Jadwal Pengangkutan</h1>
                <p class="subtitle">Lihat jadwal aktif pengangkutan sampah di lingkungan kampus.</p>
            </div>
            <a class="primary" href="#pengingat">Atur Pengingat <span aria-hidden="true">&nbsp;↗</span></a>
        </header>

        @if ($filterError)
            <div class="error" role="alert">
                {{ $filterError }}
                <a href="{{ route('schedules.public') }}">Muat ulang jadwal</a>
            </div>
        @endif

        <section class="card hero" aria-labelledby="today-title">
            <div>
                <div class="hero-label">Hari Ini · {{ $weekdayLong[$today->dayOfWeek] }}, {{ $today->format('d') }} {{ $monthNames[$today->month] }} {{ $today->year }}</div>
                <h2 id="today-title">Jadwal Pengangkutan</h2>
                @if ($highlightSchedule)
                    @php
                    $todayLocations = $selectedLocation?->nama
                        ?? ($highlightSchedule->semua_area
                            ? 'semua area'
                            : ($highlightSchedule->locations->pluck('nama')->join(', ') ?: 'area terpilih'));
                    @endphp
                    <p class="hero-copy">
                        {{ $highlightSchedule->category->nama }} · Buang pukul {{ substr($highlightSchedule->jam_buang_mulai, 0, 5) }}–{{ substr($highlightSchedule->jam_buang_selesai, 0, 5) }}
                        · Pengangkutan mulai {{ substr($highlightSchedule->jam_angkut, 0, 5) }} di {{ $todayLocations }}.
                    </p>
                @else
                    <p class="hero-copy">Belum ada jadwal aktif untuk hari ini{{ $selectedLocation ? ' di '.$selectedLocation->nama : '' }}.</p>
                @endif
            </div>
            <div class="countdown">
                <div class="countdown-label">Pengangkutan dalam</div>
                @if ($countdownSeconds !== null)
                    <div class="countdown-value" id="countdown" data-seconds="{{ $countdownSeconds }}">--:--:--</div>
                @elseif ($todaySchedules->isNotEmpty())
                    <div class="countdown-done">Pengangkutan hari ini selesai</div>
                @else
                    <div class="countdown-done">Tidak ada jadwal hari ini</div>
                @endif
            </div>
        </section>

        <section aria-labelledby="week-title">
            <div class="section-head">
                <h2 id="week-title">Minggu Ini</h2>
                <span class="section-note">{{ $weekDays->first()['date']->format('d') }} {{ $monthNames[$weekDays->first()['date']->month] }} – {{ $weekDays->last()['date']->format('d') }} {{ $monthNames[$weekDays->last()['date']->month] }}</span>
            </div>
            <div class="week-nav">
                @foreach ($weekDays as $day)
                    <a class="day-card {{ $day['selected'] ? 'selected' : '' }} {{ $day['today'] ? 'today' : '' }}"
                       href="{{ route('schedules.public', array_filter(['tanggal' => $day['date']->toDateString(), 'lokasi' => $selectedLocationId])) }}"
                       aria-current="{{ $day['selected'] ? 'date' : 'false' }}">
                        <span class="day-name">{{ $day['label'] }}</span>
                        <span class="day-date">{{ $day['date']->format('d') }}</span>
                        <span class="day-haul-time">
                            {{ $day['haulTimes'] === [] ? 'Tidak ada jadwal' : 'Angkut '.implode(' · ', $day['haulTimes']) }}
                        </span>
                        <span class="today-mark">{{ $day['today'] ? 'Hari ini' : '' }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <form class="card filters" method="GET" action="{{ route('schedules.public') }}">
            <div class="filter-field">
                <label for="tanggal">Pilih tanggal</label>
                <input id="tanggal" name="tanggal" type="date" value="{{ $selectedDate->toDateString() }}" required>
            </div>
            <div class="filter-field">
                <label for="lokasi">Lokasi</label>
                <select id="lokasi" name="lokasi">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id_lokasi }}" @selected($selectedLocationId === $location->id_lokasi)>{{ $location->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button class="primary" type="submit">Terapkan Filter</button>
                <a class="clear-link" href="{{ route('schedules.public') }}">Reset</a>
            </div>
        </form>

        <div class="content-grid">
            <section class="card table-card" aria-labelledby="table-title">
                <div class="table-title">
                    <h2 id="table-title">Jadwal per Area — {{ $weekdayLong[$selectedDate->dayOfWeek] }}, {{ $selectedDate->format('d') }} {{ $monthNames[$selectedDate->month] }}</h2>
                    <p>{{ $rows->count() }} titik pengangkutan</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Area</th><th>Titik</th><th>Kategori</th><th>Jam Buang</th><th>Jam Angkut</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($rows as $row)
                            @php
                                $schedule = $row['schedule'];
                                $categoryClass = match (strtolower($schedule->category->nama)) {
                                    'organik' => 'category-organik',
                                    'plastik' => 'category-plastik',
                                    'kertas' => 'category-kertas',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td data-label="Area">{{ $row['area'] }}</td>
                                <td data-label="Titik">{{ $row['point'] }}</td>
                                <td data-label="Kategori"><span class="category-badge {{ $categoryClass }}"><span class="category-dot"></span>{{ $schedule->category->nama }}</span></td>
                                <td data-label="Jam Buang">{{ substr($schedule->jam_buang_mulai, 0, 5) }}–{{ substr($schedule->jam_buang_selesai, 0, 5) }}</td>
                                <td data-label="Jam Angkut">{{ substr($schedule->jam_angkut, 0, 5) }}</td>
                                <td data-label="Status"><span class="status {{ $row['status'] === 'Sudah diangkut' ? 'done' : 'scheduled' }}">{{ $row['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="empty-cell" colspan="6"><div class="empty"><strong>Jadwal belum tersedia</strong>Tidak ada jadwal untuk tanggal atau lokasi yang dipilih. Coba pilih tanggal lain.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="card reminder" id="pengingat">
                <div class="reminder-icon" aria-hidden="true">♧</div>
                <div>
                    <h2>Pengingat Kamu</h2>
                    <p>Atur pengingat agar tidak melewatkan jadwal pengangkutan berikutnya.</p>
                </div>
                <a class="primary" href="#pengingat" aria-label="Atur pengingat (fitur PBI-09)">Atur Pengingat</a>
                <p class="reminder-hint">Pengaturan pengingat segera tersedia.</p>
            </aside>
        </div>
        <p class="footer-note">Jadwal mengikuti data aktif yang dikelola oleh admin. Status pengangkutan dihitung berdasarkan waktu WIB.</p>
    </main>
</div>
@if ($countdownSeconds !== null)
    <script>
        (() => {
            const countdown = document.getElementById('countdown');
            let remainingSeconds = Number(countdown.dataset.seconds);
            const renderCountdown = () => {
                if (remainingSeconds <= 0) {
                    countdown.textContent = '00:00:00';
                    return;
                }
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                countdown.textContent = [hours, minutes, seconds]
                    .map((part) => String(part).padStart(2, '0'))
                    .join(':');
                remainingSeconds -= 1;
            };
            renderCountdown();
            window.setInterval(renderCountdown, 1000);
        })();
    </script>
@endif
</body>
</html>
