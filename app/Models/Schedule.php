<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schedule extends Model
{
    protected $table = 'jadwal_pengangkutan';

    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_kategori',
        'pola',
        'interval_hari',
        'hari_ke',
        'hari',
        'berlaku_mulai',
        'berlaku_sampai',
        'jam_buang_mulai',
        'jam_buang_selesai',
        'jam_angkut',
        'catatan',
        'kirim_pengingat',
        'semua_area',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date:Y-m-d',
            'berlaku_sampai' => 'date:Y-m-d',
            'kirim_pengingat' => 'boolean',
            'semua_area' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_kategori', 'id_kategori');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'jadwal_lokasi', 'id_jadwal', 'id_lokasi');
    }
}
