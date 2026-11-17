<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriTitik extends Model
{
    protected $table = 'kategori_titik';

    protected $fillable = ['nama_kategori', 'kode', 'warna_label'];
}
