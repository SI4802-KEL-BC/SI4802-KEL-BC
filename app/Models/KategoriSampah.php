<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriSampah extends Model
{
    use HasFactory;

    protected $table = 'kategori_sampah';
    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'nama_kategori',
        'kode',
        'warna_label',
        'deskripsi',
        'karakteristik',
        'cara_penanganan',
        'status',
    ];

    public function panduanPemilahan(): HasMany
    {
        return $this->hasMany(PanduanPemilahan::class, 'id_kategori', 'id_kategori');
    }
}
