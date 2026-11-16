<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'nama_lengkap',
        'nama_panggilan',
        'peran',
        'nim_nip',
        'email_kampus',
        'program_studi',
        'no_hp',
        'kata_sandi',
        'foto_profil',
        'status_akun',
        'tanggal_bergabung',
    ];

    protected $hidden = ['kata_sandi', 'remember_token'];

    protected static function newFactory(): PenggunaFactory
    {
        return PenggunaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
            'tanggal_bergabung' => 'date',
        ];
    }

    // Login memakai kolom kata_sandi (bukan "password").
    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    public function routeNotificationForMail(): string
    {
        return $this->email_kampus;
    }

    public function isAdmin(): bool
    {
        return strtolower($this->peran) === 'admin';
    }

    public function isPetugas(): bool
    {
        return strtolower($this->peran) === 'petugas';
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isPetugas();
    }

    public function isCivitas(): bool
    {
        return in_array(strtolower($this->peran), ['mahasiswa', 'dosen', 'tendik'], true);
    }
}
