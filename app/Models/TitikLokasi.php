<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class TitikLokasi extends Model
{
    protected $table = 'titik_lokasi';

    protected $fillable = [
        'nama_lokasi', 'kode_titik', 'area', 'keterangan', 'kode_perangkat',
        'latitude', 'longitude', 'fasilitas', 'status',
    ];

    protected $casts = ['latitude' => 'float', 'longitude' => 'float'];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(KategoriTitik::class, 'titik_lokasi_kategori', 'titik_lokasi_id', 'kategori_titik_id');
    }



}
