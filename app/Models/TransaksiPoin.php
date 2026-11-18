<?php

namespace App\Models;

use Database\Factories\TransaksiPoinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** STUB PBI-13: hapus saat model transaksi_poin (PBI-17/18) masuk main. */
class TransaksiPoin extends Model
{
    use HasFactory;

    protected $table = 'transaksi_poin';

    protected $primaryKey = 'id_transaksi';

    protected $fillable = ['id_pengguna', 'id_aktivitas', 'id_aturan', 'tanggal', 'jenis', 'jumlah_poin', 'keterangan'];

    public function aturan(): BelongsTo
    {
        return $this->belongsTo(AturanReward::class, 'id_aturan', 'id_aturan');
    }

    protected function casts(): array
    {
        return ['tanggal' => 'datetime'];
    }

    protected static function newFactory(): TransaksiPoinFactory
    {
        return TransaksiPoinFactory::new();
    }
}
