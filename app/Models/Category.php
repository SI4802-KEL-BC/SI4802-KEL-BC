<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'kategori_sampah';

    protected $primaryKey = 'id_kategori';

    protected $fillable = ['nama', 'warna', 'status'];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'id_kategori', 'id_kategori');
    }
}
