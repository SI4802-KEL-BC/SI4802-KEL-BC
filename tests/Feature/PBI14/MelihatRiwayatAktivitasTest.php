<?php

namespace Tests\Feature\PBI14;

use App\Models\AktivitasPemilahan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PBI14\Concerns\BuatAktivitas;
use Tests\TestCase;

/**
 * SCRUM-37 · PBI-14 Melihat Riwayat Aktivitas Pemilahan
 *
 * Use Case  : Melihat Riwayat Aktivitas Pemilahan (UC-14)
 * Aktor     : User
 * Awal      : Pengguna sudah login
 * Akhir     : Riwayat aktivitas milik pengguna ditampilkan
 *
 * Test case TC-14-01 s.d. TC-14-10 ada di RiwayatAktivitasTest dan FilterRiwayatTest.
 * File ini menguji alur use case dari awal sampai akhir.
 */
class MelihatRiwayatAktivitasTest extends TestCase
{
    use BuatAktivitas;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Skenario Normal: buka riwayat → filter → pilih aktivitas → detail tampil. */
    public function test_use_case_skenario_normal(): void
    {
        $pengguna = Pengguna::factory()->create();
        [$lama, $ditolak, $baru] = $this->buatAktivitas($pengguna, 3, fn (int $i) => [
            'tanggal_waktu' => now()->subDays(4 - $i),
            'status_verifikasi' => $i === 2 ? AktivitasPemilahan::DITOLAK : AktivitasPemilahan::MENUNGGU,
        ])->all();
        $milikOrangLain = $this->buatAktivitas(Pengguna::factory()->create())->first();

        // 1–2. Membuka riwayat → aktivitas milik pengguna tampil, terbaru di atas.
        $this->actingAs($pengguna)->get(route('aktivitas.index'))
            ->assertOk()
            ->assertSeeInOrder([$this->kodeAkt($baru), $this->kodeAkt($ditolak), $this->kodeAkt($lama)])
            ->assertDontSee($this->kodeAkt($milikOrangLain));

        // 3–4. Memfilter → hasil sesuai filter.
        $this->actingAs($pengguna)->get(route('aktivitas.index', ['status' => AktivitasPemilahan::DITOLAK]))
            ->assertOk()
            ->assertSee($this->kodeAkt($ditolak))
            ->assertDontSee($this->kodeAkt($baru))
            ->assertDontSee($this->kodeAkt($lama));

        // 5–6. Memilih aktivitas → detail tampil.
        $this->actingAs($pengguna)->get(route('aktivitas.index'))
            ->assertSee(route('aktivitas.show', $baru));
        $this->actingAs($pengguna)->get(route('aktivitas.show', $baru))
            ->assertOk()
            ->assertSee('Detail Aktivitas '.$this->kodeAkt($baru));
    }

    /** Skenario Gagal: belum ada aktivitas → pesan belum ada aktivitas. */
    public function test_use_case_skenario_gagal(): void
    {
        $this->actingAs(Pengguna::factory()->create())->get(route('aktivitas.index'))
            ->assertOk()
            ->assertSee('Belum ada aktivitas yang tercatat')
            ->assertSee(route('pindai.index'));
    }
}
