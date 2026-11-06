<?php

namespace App\Models;

use Database\Factories\AktivitasPemilahanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        return $this->load(['kategori', 'lokasi', 'verifikasi']);
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
