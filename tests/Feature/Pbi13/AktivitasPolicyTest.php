<?php

namespace Tests\Feature\Pbi13;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Matriks AktivitasPolicy (mendukung TC-13-07, 08, 12, 15): hanya pemilik; ubah/batal hanya saat Menunggu Validasi. */
class AktivitasPolicyTest extends TestCase
{
    private function pengguna(int $id, string $peran = 'User'): Pengguna
    {
        return (new Pengguna)->forceFill(['id_pengguna' => $id, 'peran' => $peran]);
    }

    private function aktivitas(int $idPemilik, string $status): AktivitasPemilahan
    {
        return (new AktivitasPemilahan)->forceFill(['id_pengguna' => $idPemilik, 'status_verifikasi' => $status]);
    }

    /** @return array<string, array{string, bool, bool, bool}> status => [lihat, ubahCatatan, batalkan] untuk pemilik */
    public static function matriksPemilik(): array
    {
        return [
            'Menunggu Validasi' => [AktivitasPemilahan::MENUNGGU, true, true, true],
            'Terverifikasi' => [AktivitasPemilahan::TERVERIFIKASI, true, false, false],
            'Ditolak' => [AktivitasPemilahan::DITOLAK, true, false, false],
        ];
    }

    #[DataProvider('matriksPemilik')]
    public function test_pemilik_hanya_boleh_ubah_dan_batal_saat_menunggu_validasi(string $status, bool $lihat, bool $ubah, bool $batal): void
    {
        $pemilik = $this->pengguna(1);
        $aktivitas = $this->aktivitas(1, $status);

        $this->assertSame($lihat, Gate::forUser($pemilik)->allows('view', $aktivitas));
        $this->assertSame($ubah, Gate::forUser($pemilik)->allows('ubahCatatan', $aktivitas));
        $this->assertSame($batal, Gate::forUser($pemilik)->allows('batalkan', $aktivitas));
    }

    /** @return array<string, array{string, string}> */
    public static function bukanPemilik(): array
    {
        $kasus = [];
        foreach ([AktivitasPemilahan::MENUNGGU, AktivitasPemilahan::TERVERIFIKASI, AktivitasPemilahan::DITOLAK] as $status) {
            foreach (['User', 'Admin'] as $peran) {
                $kasus["$peran / $status"] = [$status, $peran];
            }
        }

        return $kasus;
    }

    #[DataProvider('bukanPemilik')]
    public function test_bukan_pemilik_tidak_boleh_apa_pun_termasuk_admin(string $status, string $peran): void
    {
        $penyusup = $this->pengguna(2, $peran);
        $aktivitas = $this->aktivitas(1, $status);

        $this->assertFalse(Gate::forUser($penyusup)->allows('view', $aktivitas));
        $this->assertFalse(Gate::forUser($penyusup)->allows('ubahCatatan', $aktivitas));
        $this->assertFalse(Gate::forUser($penyusup)->allows('batalkan', $aktivitas));
    }

    public function test_id_bertipe_string_dari_driver_database_tetap_dikenali_sebagai_pemilik(): void
    {
        $pemilik = (new Pengguna)->forceFill(['id_pengguna' => '7']);
        $aktivitas = (new AktivitasPemilahan)->forceFill(['id_pengguna' => 7, 'status_verifikasi' => AktivitasPemilahan::MENUNGGU]);

        $this->assertTrue(Gate::forUser($pemilik)->allows('batalkan', $aktivitas));
    }
}
