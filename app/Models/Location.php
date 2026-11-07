<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    protected $table = 'lokasi_tempat_sampah';

    protected $primaryKey = 'id_lokasi';

    protected $fillable = ['nama', 'status'];

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'jadwal_lokasi', 'id_lokasi', 'id_jadwal');
    }
}
