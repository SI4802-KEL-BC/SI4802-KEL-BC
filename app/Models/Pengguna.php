<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** STUB PBI-13: hapus saat model pengguna milik teman masuk main. */
class Pengguna extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = ['nama_lengkap', 'email_kampus', 'kata_sandi', 'peran', 'status_akun'];

    protected $hidden = ['kata_sandi'];

    protected static function newFactory(): PenggunaFactory
    {
        return PenggunaFactory::new();
    }

    /** PBI-15: tujuan email notifikasi (kolom email_kampus). */
    public function routeNotificationForMail(): string
    {
        return $this->email_kampus;
    }

    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    public function isAdmin(): bool
    {
        return $this->peran === 'Admin';
    }
}
