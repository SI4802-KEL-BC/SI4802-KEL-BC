<?php

namespace App\Models;

use Database\Factories\AturanRewardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** STUB PBI-13: hapus saat model aturan_reward (PBI-16) masuk main. */
class AturanReward extends Model
{
    use HasFactory;

    protected $table = 'aturan_reward';

    protected $primaryKey = 'id_aturan';

    protected $fillable = ['id_kategori', 'poin_per_satuan', 'batas_per_hari', 'status'];

    protected static function newFactory(): AturanRewardFactory
    {
        return AturanRewardFactory::new();
    }
}
