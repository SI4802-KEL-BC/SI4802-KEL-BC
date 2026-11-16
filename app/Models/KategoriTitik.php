<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriTitik extends Model
{
    protected $table = 'kategori_titik';

    protected $fillable = ['nama_kategori', 'warna_label', 'deskripsi', 'cara_penanganan'];
}
