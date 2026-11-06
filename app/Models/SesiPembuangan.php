<?php

namespace App\Models;

use Database\Factories\SesiPembuanganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SesiPembuangan extends Model
{
    use HasFactory;

    public const AKTIF = 'Aktif';

    public const SELESAI = 'Selesai';

    public const KEDALUWARSA = 'Kedaluwarsa';

    protected $table = 'sesi_pembuangan';

    protected $primaryKey = 'id_sesi';

    public $timestamps = false;

    protected $fillable = [
        'id_pengguna', 'id_lokasi', 'token_qr', 'status_sesi', 'waktu_scan', 'kedaluwarsa_pada',
    ];

    protected function casts(): array
    {
        return [
            'waktu_scan' => 'datetime',
            'kedaluwarsa_pada' => 'datetime',
        ];
    }

    protected static function newFactory(): SesiPembuanganFactory
    {
        return SesiPembuanganFactory::new();
    }

    /** Buat sesi Aktif saat QR dipindai; batas waktu = session_ttl. */
    public static function buatSesi(int $idPengguna, int $idLokasi, string $tokenHash): self
    {
        return static::create([
            'id_pengguna' => $idPengguna,
            'id_lokasi' => $idLokasi,
            'token_qr' => $tokenHash,
            'status_sesi' => self::AKTIF,
            'waktu_scan' => now(),
            'kedaluwarsa_pada' => now()->addSeconds(config('pilahku.session_ttl')),
        ]);
    }

    public function selesaikan(): void
    {
        $this->update(['status_sesi' => self::SELESAI]);
    }

    public function kedaluwarsakan(): void
    {
        $this->update(['status_sesi' => self::KEDALUWARSA]);
    }

    public function sudahLewatBatas(): bool
    {
        return $this->kedaluwarsa_pada->isPast();
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(LokasiTempatSampah::class, 'id_lokasi', 'id_lokasi');
    }

    public function aktivitas(): HasOne
    {
        return $this->hasOne(AktivitasPemilahan::class, 'id_sesi', 'id_sesi');
    }
}
