<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AturanRewardController extends Controller
{
    private const SYARAT = [
        'aktivitas_terverifikasi' => 'Aktivitas terverifikasi petugas (wajib)',
        'sesuai_jadwal' => 'Sesuai jadwal kategori hari itu',
        'minimal_satu_item' => 'Minimal 1 item per pengajuan',
        'konsistensi_lima_hari' => 'Konsisten 5 hari berturut-turut',
        'hari_bumi' => 'Berlaku pada Hari Bumi (22 April)',
    ];

    public function index(Request $request): View
    {
        return view('admin.aturan-reward.index', [
            'aturan' => AturanReward::with('kategori')->orderBy('id_aturan')->get(),
            'terpilih' => $request->filled('edit') ? AturanReward::findOrFail($request->integer('edit')) : null,
            'kategoris' => KategoriSampah::orderBy('nama_kategori')->get(),
            'syaratPilihan' => self::SYARAT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['kode_aturan'] = $this->kodeBerikutnya();
        $data['poin_per_satuan'] = $this->nilaiBertanda($data);
        $data['status'] = $request->boolean('aktif') ? 'aktif' : 'nonaktif';
        unset($data['nilai_poin'], $data['aktif']);
        $this->pastikanTidakBentrok($data);
        AturanReward::create($data);

        return redirect()->route('admin.aturan-reward.index')->with('status', 'Aturan reward berhasil ditambahkan.');
    }

    public function update(Request $request, AturanReward $aturan): RedirectResponse
    {
        $data = $this->validated($request);
        $data['poin_per_satuan'] = $this->nilaiBertanda($data);
        $data['status'] = $request->boolean('aktif') ? 'aktif' : 'nonaktif';
        unset($data['nilai_poin'], $data['aktif']);
        $this->pastikanTidakBentrok($data, $aturan);
        $aturan->update($data);

        return redirect()->route('admin.aturan-reward.index')->with('status', 'Aturan reward berhasil diperbarui.');
    }

    public function toggle(AturanReward $aturan): RedirectResponse
    {
        $statusBerikutnya = $aturan->status === 'aktif' ? 'nonaktif' : 'aktif';
        if ($statusBerikutnya === 'aktif') {
            $data = $aturan->getAttributes();
            $data['status'] = 'aktif';
            $this->pastikanTidakBentrok($data, $aturan);
        }
        $aturan->update(['status' => $statusBerikutnya]);

        return back()->with('status', 'Status aturan reward berhasil diperbarui.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_aturan' => ['required', 'string', 'max:100'],
            'jenis_aturan' => ['required', Rule::in(['Penambahan', 'Pengurangan'])],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_sampah,id_kategori'],
            'nilai_poin' => ['required', 'integer', 'min:1', 'max:1000000'],
            'satuan' => ['required', Rule::in(['per item', 'per kg', 'flat', 'pengali'])],
            'batas_per_hari' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'berlaku_mulai' => ['nullable', 'date'],
            'syarat' => ['nullable', 'array'],
            'syarat.*' => ['string', Rule::in(array_keys(self::SYARAT))],
            'aktif' => ['sometimes', 'boolean'],
        ], ['nilai_poin.min' => 'Nilai poin harus lebih besar dari 0.', 'batas_per_hari.min' => 'Batas harian tidak boleh negatif.']);
    }

    /** @param array<string, mixed> $data */
    private function nilaiBertanda(array $data): int
    {
        return $data['jenis_aturan'] === 'Pengurangan' ? -(int) $data['nilai_poin'] : (int) $data['nilai_poin'];
    }

    /** @param array<string, mixed> $data */
    private function pastikanTidakBentrok(array $data, ?AturanReward $dikecualikan = null): void
    {
        $bentrok = AturanReward::query()
            ->where('status', 'aktif')
            ->where('jenis_aturan', $data['jenis_aturan'])
            ->where('satuan', $data['satuan'])
            ->where(function (Builder $query) use ($data): void {
                $idKategori = $data['id_kategori'] ?? null;
                $query->whereNull('id_kategori');
                if ($idKategori !== null) {
                    $query->orWhere('id_kategori', $idKategori);
                } else {
                    $query->orWhereNotNull('id_kategori');
                }
            })
            ->when($dikecualikan, fn (Builder $query) => $query->where('id_aturan', '!=', $dikecualikan->id_aturan))
            ->exists();

        if ($bentrok && ($data['status'] ?? 'aktif') === 'aktif') {
            throw ValidationException::withMessages([
                'id_kategori' => 'Sudah ada aturan aktif dengan jenis dan satuan yang sama untuk cakupan ini.',
            ]);
        }
    }

    private function kodeBerikutnya(): string
    {
        $nomor = ((int) AturanReward::query()->max('id_aturan')) + 1;
        do {
            $kode = 'RWD-'.str_pad((string) $nomor++, 2, '0', STR_PAD_LEFT);
        } while (AturanReward::where('kode_aturan', $kode)->exists());

        return $kode;
    }
}
