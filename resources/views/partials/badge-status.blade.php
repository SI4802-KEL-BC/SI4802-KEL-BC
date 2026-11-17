{{-- Badge status aktivitas. Pakai: @include('partials.badge-status', ['status' => $aktivitas->status_verifikasi]) --}}
@php
    $gayaBadge = [
        'Menunggu Validasi' => 'bg-[#FEF3C7] text-[#92400E] ring-[#FDE68A] [--titik:#F59E0B]',
        'Terverifikasi' => 'bg-[#E8F7EE] text-[#166534] ring-[#BBF7D0] [--titik:#16A34A]',
        'Ditolak' => 'bg-[#FEE2E2] text-[#B91C1C] ring-[#FECACA] [--titik:#DC2626]',
    ];
@endphp
<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $gayaBadge[$status] ?? 'bg-slate-100 text-slate-600 ring-slate-200 [--titik:#94A3B8]' }}">
    <span class="h-1.5 w-1.5 rounded-full bg-[var(--titik)]"></span>
    {{ $status }}
</span>
