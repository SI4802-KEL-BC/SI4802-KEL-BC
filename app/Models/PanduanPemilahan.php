<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanduanPemilahan extends Model
{
    protected $table = 'panduan_pemilahan';

    protected $primaryKey = 'id_panduan';

    protected $fillable = [
        'id_kategori',
        'judul',
        'slug',
        'ringkasan',
        'langkah_pemilahan',
        'status',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSampah::class, 'id_kategori', 'id_kategori');
    }
}
