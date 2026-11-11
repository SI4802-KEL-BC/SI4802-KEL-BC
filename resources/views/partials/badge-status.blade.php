{{-- Badge status aktivitas. Pakai: @include('partials.badge-status', ['status' => $aktivitas->status_verifikasi]) --}}
@php
    $gayaBadge = [
        'Menunggu Validasi' => 'bg-[#FEF3C7] text-[#92400E] [--titik:#F59E0B]',
        'Terverifikasi' => 'bg-[#E8F7EE] text-[#15803D] [--titik:#16A34A]',
        'Ditolak' => 'bg-[#FEE2E2] text-[#B91C1C] [--titik:#DC2626]',
    ];
@endphp
<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $gayaBadge[$status] ?? 'bg-slate-100 text-slate-600 [--titik:#94A3B8]' }}">
    <span class="h-1.5 w-1.5 rounded-full bg-[var(--titik)]"></span>
    {{ $status }}
</span>
