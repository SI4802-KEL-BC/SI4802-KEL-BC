@extends('layouts.app')

@section('judul', 'QR ID Saya · PilahKu')

@section('isi')
    <section class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
        <h1 class="text-xl font-semibold">QR ID Saya</h1>
        <p class="mt-1 text-sm text-slate-500">
            Pindai QR ini pada perangkat tempat sampah. QR hanya berlaku {{ $qrTtl }} detik dan sekali pakai.
        </p>

        <div id="qr-gambar" class="mx-auto mt-6 w-fit rounded-xl bg-white p-3 ring-1 ring-slate-200" aria-label="QR ID sementara">
            {!! $qrSvg !!}
        </div>

        <p class="mt-4 text-sm">
            Berlaku:
            <span id="hitung-mundur" data-sisa-detik="{{ $sisaDetik }}" class="font-mono text-lg font-semibold tabular-nums">
                {{ sprintf('%02d:%02d', intdiv($sisaDetik, 60), $sisaDetik % 60) }}
            </span>
        </p>
        <p id="pesan-kedaluwarsa" class="mt-1 hidden text-sm font-medium text-red-600" role="alert">
            QR sudah kedaluwarsa. Buat QR baru.
        </p>

        <form method="POST" action="{{ route('qr-id.regenerate') }}" class="mt-6">
            @csrf
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Buat Ulang QR
            </button>
        </form>
    </section>

    <script>
        (function () {
            const el = document.getElementById('hitung-mundur');
            let sisa = parseInt(el.dataset.sisaDetik, 10);
            const tampil = () => {
                el.textContent = String(Math.floor(sisa / 60)).padStart(2, '0') + ':' + String(sisa % 60).padStart(2, '0');
            };
            const timer = setInterval(() => {
                sisa = Math.max(0, sisa - 1);
                tampil();
                if (sisa === 0) {
                    clearInterval(timer);
                    document.getElementById('qr-gambar').classList.add('opacity-30');
                    document.getElementById('pesan-kedaluwarsa').classList.remove('hidden');
                }
            }, 1000);
            if (sisa === 0) {
                document.getElementById('qr-gambar').classList.add('opacity-30');
                document.getElementById('pesan-kedaluwarsa').classList.remove('hidden');
            }
        })();
    </script>
@endsection
