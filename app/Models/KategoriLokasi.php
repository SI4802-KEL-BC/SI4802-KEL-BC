<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** STUB PBI-13: tabel pivot kategori_lokasi (PK gabungan). */
class KategoriLokasi extends Model
{
    protected $table = 'kategori_lokasi';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = ['id_kategori', 'id_lokasi'];
}
