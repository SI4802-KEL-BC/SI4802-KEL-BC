<?php

namespace Database\Seeders;

use App\Models\AktivitasPemilahan;
use App\Models\AturanReward;
use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Models\SesiPembuangan;
use App\Models\TransaksiPoin;
use App\Models\VerifikasiSampah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data uji coba PBI-13 (hanya untuk development lokal).
 *
 *   User  : user@pilahku.test  / password
 *   Admin : admin@pilahku.test / password
 */
class PilahkuDemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** nama_kategori => [kode, warna_label, poin_per_satuan] */
    private const KATEGORI = [
        'Organik' => ['ORG', '#22c55e', 5],
        'Plastik' => ['PLS', '#3b82f6', 10],
        'Kertas' => ['KRT', '#eab308', 5],
        'Logam' => ['LGM', '#64748b', 15],
        'Kaca' => ['KCA', '#14b8a6', 15],
    ];

    /** kode_perangkat => [nama_lokasi, kode_titik, area, kategori yang diterima] */
    private const LOKASI = [
        'DEV-0001' => ['Kantin TULT', 'T-01', 'Kantin', ['Plastik', 'Kertas']],
        'DEV-0002' => ['Gedung Kuliah Umum', 'T-02', 'Akademik', ['Organik']],
        'DEV-0003' => ['Gedung Rektorat', 'T-03', 'Administrasi', ['Logam', 'Kaca', 'Plastik']],
    ];

    public function run(): void
    {
        $user = Pengguna::updateOrCreate(
            ['email_kampus' => 'user@pilahku.test'],
            ['nama_lengkap' => 'Pengguna Uji', 'kata_sandi' => Hash::make(self::PASSWORD), 'peran' => 'User', 'status_akun' => 'aktif'],
        );

        Pengguna::updateOrCreate(
            ['email_kampus' => 'admin@pilahku.test'],
            ['nama_lengkap' => 'Admin Uji', 'kata_sandi' => Hash::make(self::PASSWORD), 'peran' => 'Admin', 'status_akun' => 'aktif'],
        );

        // Kategori (dibuat hanya bila belum ada, agar data milik SCRUM-27 tidak ditimpa) + aturan reward aktif.
        $kategori = [];
        foreach (self::KATEGORI as $nama => [$kode, $warna, $poin]) {
            $kategori[$nama] = $this->kategori($nama, $kode, $warna);
            AturanReward::updateOrCreate(
                ['id_kategori' => $kategori[$nama]->id_kategori],
                ['poin_per_satuan' => $poin, 'status' => 'aktif'],
            );
        }
        [$plastik, $kertas, $organik] = [$kategori['Plastik'], $kategori['Kertas'], $kategori['Organik']];

        // Lokasi + kode perangkat; kategori yang diterima di-sync persis sesuai daftar (aman dijalankan ulang).
        $lokasiDemo = [];
        foreach (self::LOKASI as $kodePerangkat => [$namaLokasi, $kodeTitik, $area, $diterima]) {
            $lokasiDemo[$kodePerangkat] = LokasiTempatSampah::updateOrCreate(
                ['kode_perangkat' => $kodePerangkat],
                ['nama_lokasi' => $namaLokasi, 'kode_titik' => $kodeTitik, 'area' => $area, 'status' => 'aktif'],
            );
            $lokasiDemo[$kodePerangkat]->kategoriDiterima()->sync(
                array_map(fn (string $nama) => $kategori[$nama]->id_kategori, $diterima),
            );
        }
        $lokasi = $lokasiDemo['DEV-0001']; // aktivitas contoh di bawah dibuat di Kantin TULT (menerima Plastik & Kertas)

        if (AktivitasPemilahan::where('id_pengguna', $user->id_pengguna)->exists()) {
            return; // aktivitas contoh sudah ada; seeder aman dijalankan ulang
        }

        // 1) Menunggu Validasi, skor rendah => masuk antrean Admin (Ragu)
        $a1 = $this->aktivitas($user, $lokasi, $plastik, 1, now()->subMinutes(20), AktivitasPemilahan::MENUNGGU, 'Botol air mineral');
        VerifikasiSampah::verifikasiAwal($a1, VerifikasiSampah::RAGU, 'plastic', 0.55);

        // 2) Menunggu Validasi lain (tanpa catatan)
        $a2 = $this->aktivitas($user, $lokasi, $kertas, 2, now()->subHour(), AktivitasPemilahan::MENUNGGU);
        VerifikasiSampah::verifikasiAwal($a2, VerifikasiSampah::RAGU, 'paper', 0.62);

        // 3) Terverifikasi + 10 poin
        $a3 = $this->aktivitas($user, $lokasi, $plastik, 3, now()->subDay(), AktivitasPemilahan::TERVERIFIKASI, 'Gelas plastik kantin', poin: 10);
        VerifikasiSampah::verifikasiAwal($a3, VerifikasiSampah::VALID, 'plastic', 0.93);
        TransaksiPoin::create([
            'id_pengguna' => $user->id_pengguna,
            'id_aktivitas' => $a3->id_aktivitas,
            'tanggal' => $a3->tanggal_waktu,
            'jenis' => 'Perolehan',
            'jumlah_poin' => 10,
            'keterangan' => 'Poin aktivitas pemilahan #'.$a3->id_aktivitas,
        ]);

        // 4) Ditolak: kategori tidak diterima lokasi
        $a4 = $this->aktivitas(
            $user, $lokasi, $organik, 4, now()->subDays(2), AktivitasPemilahan::DITOLAK,
            alasan: 'Kategori tidak sesuai tempat sampah',
        );
        VerifikasiSampah::verifikasiAwal($a4, VerifikasiSampah::TIDAK_VALID, 'organik', 0.88, 'Kategori tidak sesuai tempat sampah');
    }

    private function kategori(string $nama, string $kode, string $warna): KategoriSampah
    {
        return KategoriSampah::firstOrCreate(
            ['nama_kategori' => $nama],
            ['kode' => $kode, 'warna_label' => $warna, 'status' => 'aktif'],
        );
    }

    private function aktivitas(
        Pengguna $user,
        LokasiTempatSampah $lokasi,
        KategoriSampah $kategori,
        int $nomor,
        \DateTimeInterface $waktu,
        string $status,
        ?string $catatan = null,
        int $poin = 0,
        ?string $alasan = null,
    ): AktivitasPemilahan {
        $sesi = SesiPembuangan::create([
            'id_pengguna' => $user->id_pengguna,
            'id_lokasi' => $lokasi->id_lokasi,
            'token_qr' => hash('sha256', "demo-sesi-{$nomor}"),
            'status_sesi' => SesiPembuangan::SELESAI,
            'waktu_scan' => $waktu,
            'kedaluwarsa_pada' => (clone $waktu)->modify('+5 minutes'),
        ]);

        return AktivitasPemilahan::create([
            'id_pengguna' => $user->id_pengguna,
            'id_kategori' => $kategori->id_kategori,
            'id_lokasi' => $lokasi->id_lokasi,
            'id_sesi' => $sesi->id_sesi,
            'tanggal_waktu' => $waktu,
            'jumlah' => 1,
            'foto_bukti' => $this->foto($kategori->nama_kategori, $nomor, $kategori->warna_label ?? '#64748b'),
            'catatan' => $catatan,
            'status_verifikasi' => $status,
            'alasan_penolakan' => $alasan,
            'poin_diperoleh' => $poin,
        ]);
    }

    /** Foto contoh berupa gambar SVG sederhana di disk "public". */
    private function foto(string $label, int $nomor, string $warna): string
    {
        $path = "bukti/demo-{$nomor}.svg";
        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="480" height="320" viewBox="0 0 480 320">
          <rect width="480" height="320" fill="{$warna}" opacity="0.25"/>
          <text x="240" y="170" font-family="sans-serif" font-size="36" text-anchor="middle" fill="#334155">Foto contoh: {$label}</text>
        </svg>
        SVG;
        Storage::disk('public')->put($path, $svg);

        return $path;
    }
}
