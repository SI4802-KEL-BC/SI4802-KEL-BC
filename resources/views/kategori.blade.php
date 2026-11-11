<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori Sampah</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

<div class="space-y-6 p-6 max-w-7xl mx-auto" x-data="{
    isEdit: false,
    id_kategori: null,
    nama_kategori: '',
    kode: '',
    warna_label: 'bg-emerald-500',
    deskripsi: '',
    cara_pembuangan: '',
    status: true,

    resetForm() {
        this.isEdit = false;
        this.id_kategori = null;
        this.nama_kategori = '';
        this.kode = '';
        this.warna_label = 'bg-emerald-500';
        this.deskripsi = '';
        this.cara_pembuangan = '';
        this.status = true;
    },

    editKategori(k) {
        this.isEdit = true;
        this.id_kategori = k.id_kategori;
        this.nama_kategori = k.nama_kategori;
        this.kode = k.kode;
        this.warna_label = k.warna_label || 'bg-emerald-500';
        this.deskripsi = k.deskripsi || '';
        this.cara_pembuangan = k.cara_pembuangan || '';
        this.status = k.status === 'aktif';
        document.getElementById('formKategoriPanel').scrollIntoView({behavior: 'smooth'});
    }
}">

    {{-- Notifikasi Flash Message --}}
    @if (session('status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-800 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Kategori Sampah</h1>
            <p class="mt-0.5 text-sm text-slate-500">Tambah, ubah, atau nonaktifkan kategori yang tampil di panduan dan pencarian.</p>
        </div>
        <button type="button" @click="resetForm(); document.getElementById('formKategoriPanel').scrollIntoView({behavior: 'smooth'})"
                class="inline-flex items-center gap-1.5 rounded-xl bg-[#1fa959] hover:bg-[#188e4a] px-4 py-2 text-sm font-semibold text-white transition shadow-sm shrink-0">
            <span>+</span>
            <span>Tambah Kategori</span>
        </button>
    </div>

    {{-- Main Grid: Table Left, Edit Panel Right --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Left Table (7 Cols) --}}
        <div class="lg:col-span-7 rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50/50 border-b border-slate-100">
                            <th class="px-4 py-3.5">Kategori</th>
                            <th class="px-4 py-3.5">Kode</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse ($kategoris as $k)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-4 py-4 font-semibold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full {{ $k->warna_label ?? 'bg-emerald-500' }}"></span>
                                        {{ $k->nama_kategori }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-mono font-semibold text-slate-500">
                                    {{ $k->kode }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if ($k->status === 'aktif')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-500">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap space-x-2">
                                    <button type="button" @click="editKategori({{ json_encode($k) }})" class="text-xs font-semibold text-slate-700 hover:text-emerald-700">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('kategori.toggle', $k->id_kategori) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold {{ $k->status === 'aktif' ? 'text-rose-600 hover:text-rose-700' : 'text-emerald-600 hover:text-emerald-700' }}">
                                            {{ $k->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-400 text-xs">
                                    Belum ada kategori sampah.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right Form Panel (Dynamic Add/Edit) (5 Cols) --}}
        <div id="formKategoriPanel" class="lg:col-span-5 rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Kategori: ' + nama_kategori : 'Tambah Kategori Baru'"></h3>
                <button type="button" @click="resetForm()" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
            </div>

            <form method="POST" :action="isEdit ? '{{ url('kategori') }}/' + id_kategori : '{{ route('kategori.store') }}'" class="space-y-4 text-xs">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Nama Kategori --}}
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_kategori" x-model="nama_kategori" required
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500" placeholder="Contoh: Plastik">
                </div>

                {{-- Kode & Warna Label --}}
                <div class="grid grid-cols-2 gap-3 items-center">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Kode <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode" x-model="kode" required
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500" placeholder="PLS">
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Warna Label</label>
                        <div class="flex items-center gap-1.5 pt-1">
                            <template x-for="color in ['bg-emerald-500', 'bg-blue-500', 'bg-amber-500', 'bg-orange-500', 'bg-purple-500', 'bg-rose-500']">
                                <span @click="warna_label = color" 
                                    :class="[color, warna_label === color ? 'ring-2 ring-slate-900 scale-110' : 'ring-2 ring-transparent hover:ring-slate-400']"
                                    class="h-5 w-5 rounded-full cursor-pointer transition-all inline-block"></span>
                            </template>
                        </div>
                        <input type="hidden" name="warna_label" x-model="warna_label">
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Deskripsi <span class="text-rose-500">*</span></label>
                    <textarea name="deskripsi" x-model="deskripsi" rows="2" required
                              class="w-full rounded-xl border border-slate-200 bg-white p-3 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500" placeholder="Deskripsi kategori..."></textarea>
                </div>

                {{-- Cara Penanganan --}}
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Cara Penanganan <span class="text-rose-500">*</span></label>
                    <textarea name="cara_pembuangan" x-model="cara_pembuangan" rows="2" required
                              class="w-full rounded-xl border border-slate-200 bg-white p-3 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500" placeholder="Cara penanganan..."></textarea>
                </div>

                {{-- Kategori Aktif Toggle --}}
                <div class="flex items-center justify-between pt-2">
                    <div>
                        <p class="font-bold text-slate-700">Status Aktif</p>
                        <p class="text-[11px] text-slate-400">Tampil di panduan &amp; pencarian</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="status" value="aktif" x-model="status" class="sr-only peer">
                        <div class="w-10 h-5 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#1fa959]"></div>
                    </label>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="resetForm()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-[#1fa959] hover:bg-[#188e4a] px-4 py-2 text-xs font-semibold text-white transition shadow-sm">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

</body>
</html>