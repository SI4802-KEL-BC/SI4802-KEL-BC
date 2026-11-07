<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LokasiTempatSampah extends Model
{
    protected $table = 'lokasi_tempat_sampah';
    protected $primaryKey = 'id_lokasi';
    protected $fillable = ['nama_lokasi', 'kode_titik', 'latitude', 'longitude', 'status'];
    protected $casts = ['latitude' => 'float', 'longitude' => 'float'];

    public function scopeAktif(Builder $q): Builder { return $q->where('status', 'aktif'); }

    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(KategoriSampah::class, 'kategori_lokasi', 'id_lokasi', 'id_kategori');
    }

    public function jadwal(): HasMany { return $this->hasMany(JadwalPengangkutan::class, 'id_lokasi'); }
    public function aktivitas(): HasMany { return $this->hasMany(AktivitasPemilahan::class, 'id_lokasi'); }
}
