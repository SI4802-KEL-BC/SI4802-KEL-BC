<?php

namespace App\Models;

use Database\Factories\LokasiTempatSampahFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LokasiTempatSampah extends Model
{
    use HasFactory;

    protected $table = 'lokasi_tempat_sampah';

    protected $primaryKey = 'id_lokasi';

    protected $fillable = [
        'nama_lokasi',
        'kode_titik',
        'area',
        'kode_perangkat',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function newFactory(): LokasiTempatSampahFactory
    {
        return LokasiTempatSampahFactory::new();
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(KategoriSampah::class, 'kategori_lokasi', 'id_lokasi', 'id_kategori');
    }

    public function kategoriDiterima(): BelongsToMany
    {
        return $this->belongsToMany(KategoriSampah::class, 'kategori_lokasi', 'id_lokasi', 'id_kategori');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasPemilahan::class, 'id_lokasi', 'id_lokasi');
    }
}
