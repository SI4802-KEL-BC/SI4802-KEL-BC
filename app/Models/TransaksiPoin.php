<?php

namespace App\Models;

use Database\Factories\TransaksiPoinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** STUB PBI-13: hapus saat model transaksi_poin (PBI-17/18) masuk main. */
class TransaksiPoin extends Model
{
    use HasFactory;

    protected $table = 'transaksi_poin';

    protected $primaryKey = 'id_transaksi';

    protected $fillable = ['id_pengguna', 'id_aktivitas', 'tanggal', 'jenis', 'jumlah_poin', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'datetime'];
    }

    protected static function newFactory(): TransaksiPoinFactory
    {
        return TransaksiPoinFactory::new();
    }
}
