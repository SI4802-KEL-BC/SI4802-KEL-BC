<?php

namespace App\Models;

use Database\Factories\VerifikasiSampahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiSampah extends Model
{
    use HasFactory;

    public const TAHAP_OTOMATIS = 1;

    public const TAHAP_MANUAL = 2;

    public const VALID = 'Valid';

    public const TIDAK_VALID = 'Tidak Valid';

    public const RAGU = 'Ragu';

    protected $table = 'verifikasi_sampah';

    protected $primaryKey = 'id_verifikasi';

    public $timestamps = false;

    protected $fillable = [
        'id_aktivitas', 'id_verifikator', 'tahap', 'hasil', 'skor_keyakinan',
        'label_terdeteksi', 'foto_sampah', 'waktu_verifikasi', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tahap' => 'integer',
            'skor_keyakinan' => 'decimal:2',
            'waktu_verifikasi' => 'datetime',
        ];
    }

    protected static function newFactory(): VerifikasiSampahFactory
    {
        return VerifikasiSampahFactory::new();
    }

    /** Tahap 1 (otomatis): id_verifikator NULL. */
    public static function verifikasiAwal(
        AktivitasPemilahan $aktivitas,
        string $hasil,
        ?string $label,
        ?float $skor,
        ?string $keterangan = null,
    ): self {
        return static::create([
            'id_aktivitas' => $aktivitas->id_aktivitas,
            'id_verifikator' => null,
            'tahap' => self::TAHAP_OTOMATIS,
            'hasil' => $hasil,
            'skor_keyakinan' => $skor,
            'label_terdeteksi' => $label,
            'foto_sampah' => $aktivitas->foto_bukti,
            'waktu_verifikasi' => now(),
            'keterangan' => $keterangan,
        ]);
    }

    /** Tahap 2 (manual oleh Admin): menyimpan id_verifikator. */
    public static function verifikasiManual(
        AktivitasPemilahan $aktivitas,
        Pengguna $admin,
        string $hasil,
        ?string $keterangan = null,
    ): self {
        return static::create([
            'id_aktivitas' => $aktivitas->id_aktivitas,
            'id_verifikator' => $admin->id_pengguna,
            'tahap' => self::TAHAP_MANUAL,
            'hasil' => $hasil,
            'foto_sampah' => $aktivitas->foto_bukti,
            'waktu_verifikasi' => now(),
            'keterangan' => $keterangan,
        ]);
    }

    public function aktivitas(): BelongsTo
    {
        return $this->belongsTo(AktivitasPemilahan::class, 'id_aktivitas', 'id_aktivitas');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_verifikator', 'id_pengguna');
    }
}
