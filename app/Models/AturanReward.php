<?php

namespace App\Models;

use Database\Factories\AturanRewardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AturanReward extends Model
{
    use HasFactory;

    protected $table = 'aturan_reward';

    protected $primaryKey = 'id_aturan';

    protected $fillable = [
        'id_kategori',
        'kode_aturan',
        'nama_aturan',
        'jenis_aturan',
        'poin_per_satuan',
        'satuan',
        'syarat',
        'batas_per_hari',
        'berlaku_mulai',
        'status',
    ];

    protected function casts(): array
    {
        return ['syarat' => 'array', 'berlaku_mulai' => 'date'];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSampah::class, 'id_kategori', 'id_kategori');
    }

    protected static function newFactory(): AturanRewardFactory
    {
        return AturanRewardFactory::new();
    }
}
