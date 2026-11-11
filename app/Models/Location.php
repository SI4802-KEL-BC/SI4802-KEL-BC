<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    protected $table = 'lokasi_tempat_sampah';

    protected $primaryKey = 'id_lokasi';

    protected $fillable = ['nama', 'nama_lokasi', 'kode_titik', 'area', 'kode_perangkat', 'status'];

    public function getNamaAttribute(): ?string
    {
        return $this->attributes['nama_lokasi'] ?? null;
    }

    public function setNamaAttribute(string $value): void
    {
        $this->attributes['nama_lokasi'] = $value;
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'jadwal_lokasi', 'id_lokasi', 'id_jadwal');
    }
}
