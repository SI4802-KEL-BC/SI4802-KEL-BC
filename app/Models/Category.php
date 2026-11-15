<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'kategori_sampah';

    protected $primaryKey = 'id_kategori';

    protected $fillable = ['nama', 'nama_kategori', 'warna', 'warna_label', 'status', 'kode'];

    public function getNamaAttribute(): ?string
    {
        return $this->attributes['nama_kategori'] ?? null;
    }

    public function setNamaAttribute(string $value): void
    {
        $this->attributes['nama_kategori'] = $value;
    }

    public function getWarnaAttribute(): ?string
    {
        return $this->attributes['warna_label'] ?? '#6B7280';
    }

    public function setWarnaAttribute(?string $value): void
    {
        $this->attributes['warna_label'] = $value;
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'id_kategori', 'id_kategori');
    }
}
