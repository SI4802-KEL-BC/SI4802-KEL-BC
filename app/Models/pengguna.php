<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'nama_lengkap', 'nama_panggilan', 'peran', 'nim_nip', 'email_kampus',
        'program_studi', 'no_hp', 'kata_sandi', 'foto_profil', 'status_akun', 'tanggal_bergabung',
    ];

    protected $hidden = ['kata_sandi', 'remember_token'];

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

    public function isAdmin(): bool { return $this->peran === 'admin'; }
    public function isPetugas(): bool { return $this->peran === 'petugas'; }
    public function isStaff(): bool { return $this->isAdmin() || $this->isPetugas(); }
    public function isCivitas(): bool { return in_array($this->peran, ['mahasiswa', 'dosen', 'tendik'], true); }

    //public function aktivitas(): HasMany { return $this->hasMany(AktivitasPemilahan::class, 'id_pengguna'); }
   // public function transaksiPoin(): HasMany { return $this->hasMany(TransaksiPoin::class, 'id_pengguna'); }
    //public function pengingat(): HasMany { return $this->hasMany(PengingatJadwal::class, 'id_pengguna'); }

}
