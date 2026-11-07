<?php

namespace App\Models;

use Database\Factories\AktivitasPemilahanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;

class AktivitasPemilahan extends Model
{
    use HasFactory;

    public const MENUNGGU = 'Menunggu Validasi';

    public const TERVERIFIKASI = 'Terverifikasi';

    public const DITOLAK = 'Ditolak';

    protected $table = 'aktivitas_pemilahan';

    protected $primaryKey = 'id_aktivitas';

    public $timestamps = false;

    protected $fillable = [
        'id_pengguna', 'id_kategori', 'id_lokasi', 'id_sesi', 'tanggal_waktu', 'jumlah',
        'foto_bukti', 'catatan', 'status_verifikasi', 'alasan_penolakan', 'poin_diperoleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_waktu' => 'datetime',
            'jumlah' => 'decimal:2',
            'poin_diperoleh' => 'integer',
        ];
    }

    protected static function newFactory(): AktivitasPemilahanFactory
    {
        return AktivitasPemilahanFactory::new();
    }

    /** Simpan aktivitas baru: selalu berstatus Menunggu Validasi dan terhubung ke sesi. */
    public static function simpan(array $data): self
    {
        return static::create($data + [
            'tanggal_waktu' => now(),
            'jumlah' => 1,
            'status_verifikasi' => self::MENUNGGU,
            'poin_diperoleh' => 0,
        ]);
    }

    public function lihatDetail(): self
    {
        return $this->load(['kategori', 'lokasi', 'sesi', 'verifikasi.verifikator']);
    }

    public function ubahCatatan(?string $catatan): void
    {
        $this->update(['catatan' => $catatan]);
    }

    /** Batalkan = hapus aktivitas (verifikasi ikut terhapus via cascade). */
    public function batalkan(): void
    {
        $this->delete();
    }

    /**
     * Antrean verifikasi manual Admin: masih Menunggu Validasi, hasil tahap 1 "Ragu",
     * dan belum punya verifikasi tahap 2.
     */
    public function scopeAntreanManual(Builder $query): Builder
    {
        return $query
            ->where('status_verifikasi', self::MENUNGGU)
            ->whereHas('verifikasi', fn (Builder $q) => $q
                ->where('tahap', VerifikasiSampah::TAHAP_OTOMATIS)
                ->where('hasil', VerifikasiSampah::RAGU))
            ->whereDoesntHave('verifikasi', fn (Builder $q) => $q
                ->where('tahap', VerifikasiSampah::TAHAP_MANUAL));
    }

    /** PBI-14: hanya aktivitas milik pengguna tertentu. */
    public function scopeMilik(Builder $query, Pengguna $pengguna): Builder
    {
        return $query->where('id_pengguna', $pengguna->getKey());
    }

    /** PBI-14: filter dari/sampai (tanggal, inklusif), kategori (id_kategori), status; semua opsional. */
    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when(! empty($filter['dari']), fn (Builder $q) => $q->whereDate('tanggal_waktu', '>=', $filter['dari']))
            ->when(! empty($filter['sampai']), fn (Builder $q) => $q->whereDate('tanggal_waktu', '<=', $filter['sampai']))
            ->when(! empty($filter['kategori']), fn (Builder $q) => $q->where('id_kategori', $filter['kategori']))
            ->when(! empty($filter['status']), fn (Builder $q) => $q->where('status_verifikasi', $filter['status']));
    }

    /** PBI-14: riwayat milik pengguna, terbaru di atas, eager load kategori & lokasi. */
    public static function lihatRiwayat(Pengguna $pengguna, array $filter = [], int $perHalaman = 10): LengthAwarePaginator
    {
        return static::query()
            ->with(['kategori', 'lokasi'])
            ->milik($pengguna)
            ->filter($filter)
            ->orderByDesc('tanggal_waktu')
            ->orderByDesc('id_aktivitas')
            ->paginate($perHalaman)
            ->withQueryString();
    }

    /** PBI-14: ringkasan total & per status (tanpa filter) dalam satu query. */
    public static function ringkasan(Pengguna $pengguna): array
    {
        $per = static::query()->milik($pengguna)
            ->selectRaw('status_verifikasi, COUNT(*) as jumlah')
            ->groupBy('status_verifikasi')
            ->pluck('jumlah', 'status_verifikasi');

        return [
            'total' => (int) $per->sum(),
            'terverifikasi' => (int) ($per[self::TERVERIFIKASI] ?? 0),
            'menunggu' => (int) ($per[self::MENUNGGU] ?? 0),
            'ditolak' => (int) ($per[self::DITOLAK] ?? 0),
        ];
    }

    public function menungguValidasi(): bool
    {
        return $this->status_verifikasi === self::MENUNGGU;
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSampah::class, 'id_kategori', 'id_kategori');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(LokasiTempatSampah::class, 'id_lokasi', 'id_lokasi');
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiPembuangan::class, 'id_sesi', 'id_sesi');
    }

    public function verifikasi(): HasMany
    {
        return $this->hasMany(VerifikasiSampah::class, 'id_aktivitas', 'id_aktivitas');
    }

    public function transaksiPoin(): HasOne
    {
        return $this->hasOne(TransaksiPoin::class, 'id_aktivitas', 'id_aktivitas');
    }
}
