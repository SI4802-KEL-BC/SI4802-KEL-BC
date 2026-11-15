{{--
    PBI-15 · Panel "Status Verifikasi" (detail aktivitas).
    Variabel: $aktivitas (relasi verifikasi.verifikator, verifikasiTerakhir, transaksiPoin sudah dimuat),
              $info = $aktivitas->lihatStatus()
--}}
@php
    $status = $aktivitas->status_verifikasi;
    $v1 = $aktivitas->verifikasi->firstWhere('tahap', 1);
    $v2 = $aktivitas->verifikasi->firstWhere('tahap', 2);
    $ragu = $info['ragu'];
    $persen = $info['skor'] !== null ? (int) round($info['skor'] * 100) : null;
    $warnaBar = $ragu ? 'bg-[#F59E0B]' : ($v1 && $v1->hasil === 'Valid' ? 'bg-[#16A34A]' : 'bg-[#DC2626]');
@endphp
<section id="panel-status-verifikasi" class="rounded-2xl border border-[#E1EAE4] bg-white p-6">
    <h2 class="text-base font-semibold">Status Verifikasi</h2>

    <dl class="mt-4 space-y-3 text-sm">
        @if ($info['selesai'])
            <div class="flex justify-between gap-4">
                <dt class="text-[#5B6B62]">Sumber verifikasi</dt>
                <dd class="text-right font-semibold">{{ $info['sumber'] }}@if ($info['nama_admin']) · {{ $info['nama_admin'] }}@endif</dd>
            </div>
        @else
            <div class="flex justify-between gap-4">
                <dt class="text-[#5B6B62]">Keterangan</dt>
                <dd class="text-right font-semibold text-[#92400E]">{{ $info['info'] }}</dd>
            </div>
        @endif
        @if ($info['label'] !== null)
            <div class="flex justify-between gap-4">
                <dt class="text-[#5B6B62]">Label terdeteksi</dt>
                <dd class="text-right font-semibold">{{ $info['label'] }}</dd>
            </div>
        @endif
        @if ($persen !== null)
            <div class="flex items-center justify-between gap-4">
                <dt class="text-[#5B6B62]">Keyakinan</dt>
                <dd class="flex items-center gap-2 font-semibold">
                    <span class="h-1.5 w-20 overflow-hidden rounded-full bg-[#E1EAE4]"><span class="block h-full rounded-full {{ $warnaBar }}" style="width: {{ $persen }}%"></span></span>
                    {{ $persen }}%
                </dd>
            </div>
        @endif
        <div class="flex justify-between gap-4">
            <dt class="text-[#5B6B62]">Poin</dt>
            <dd class="text-right font-semibold {{ $status === 'Terverifikasi' ? 'text-[#15803D]' : '' }}">
                {{ $info['poin'] === null ? '–' : ($status === 'Terverifikasi' ? '+' . $info['poin'] : $info['poin']) }}
            </dd>
        </div>
        @if ($info['waktu'])
            <div class="flex justify-between gap-4">
                <dt class="text-[#5B6B62]">Waktu verifikasi</dt>
                <dd class="text-right font-semibold">{{ $info['waktu']->locale('id')->translatedFormat('d M Y, H:i') }}</dd>
            </div>
        @endif
        @if ($info['alasan'] !== null)
            <div class="rounded-xl bg-[#FEF2F2] px-3 py-2 text-[#B91C1C]">
                <dt class="text-xs font-semibold">Alasan penolakan</dt>
                <dd class="mt-0.5">{{ $info['alasan'] }}</dd>
            </div>
        @endif
    </dl>

    @unless ($info['selesai'])
        <p class="mt-3 text-xs text-[#5B6B62]">Status diperiksa otomatis setiap 10 detik.</p>
    @endunless

    <h3 class="mt-6 border-t border-[#E9F0EC] pt-5 text-xs font-semibold uppercase tracking-wide text-[#5B6B62]">Riwayat proses</h3>
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
            'Terverifikasi' => ['Hasil: Terverifikasi', '+' . $info['poin'] . ' poin ditambahkan', 'selesai'],
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
