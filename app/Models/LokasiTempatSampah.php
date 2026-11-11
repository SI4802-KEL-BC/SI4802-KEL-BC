<?php

namespace App\Models;

use Database\Factories\LokasiTempatSampahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** STUB PBI-13: hapus saat model lokasi milik teman masuk main. */
class LokasiTempatSampah extends Model
{
    use HasFactory;

    protected $table = 'lokasi_tempat_sampah';

    protected $primaryKey = 'id_lokasi';

    protected $fillable = ['nama_lokasi', 'kode_titik', 'area', 'kode_perangkat', 'status'];

    protected static function newFactory(): LokasiTempatSampahFactory
    {
        return LokasiTempatSampahFactory::new();
    }

    public function kategoriDiterima(): BelongsToMany
    {
        return $this->belongsToMany(KategoriSampah::class, 'kategori_lokasi', 'id_lokasi', 'id_kategori');
    }
}
