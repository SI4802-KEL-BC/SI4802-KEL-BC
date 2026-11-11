<?php

namespace Tests\Feature\Pbi13\Concerns;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Services\VerifikasiOtomatisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * Pembantu bersama test PBI-13: pengguna memindai QR tong (/pindai/{kode}), lalu kamera tong
 * mengirim foto ke /api/perangkat/foto untuk tongnya.
 */
trait SiapkanPerangkat
{
    protected Pengguna $pengguna;

    protected LokasiTempatSampah $lokasi;

    protected function siapkanPerangkat(): void
    {
        Storage::fake('public');
        config(['pilahku.device_tokens' => ['token-test']]);

        $this->pengguna = Pengguna::factory()->create();
        $this->lokasi = LokasiTempatSampah::factory()->create();
    }

    /** @return array<string, string> */
    protected function headerPerangkat(): array
    {
        return ['X-Device-Token' => 'token-test'];
    }

    /** Pengguna memindai QR yang tertempel di tong: membuka URL /pindai/{kode_perangkat}. */
    protected function pindai(?Pengguna $pengguna = null, ?string $kodePerangkat = null): TestResponse
    {
        return $this->actingAs($pengguna ?? $this->pengguna)
            ->get('/pindai/'.($kodePerangkat ?? $this->lokasi->kode_perangkat));
    }

    /** Kamera tong mengirim foto untuk tongnya sendiri (bukan untuk pengguna tertentu). */
    protected function kirimFoto(?string $kodePerangkat = null, ?array $headers = null, ?UploadedFile $foto = null): TestResponse
    {
        return $this->postJson('/api/perangkat/foto', [
            'kode_perangkat' => $kodePerangkat ?? $this->lokasi->kode_perangkat,
            'foto' => $foto ?? $this->fotoPalsu(),
        ], $headers ?? $this->headerPerangkat());
    }

    protected function fotoPalsu(): UploadedFile
    {
        return UploadedFile::fake()->image('sampah.jpg', 64, 64);
    }

    /** Ganti model klasifikasi dengan hasil yang ditentukan test. */
    protected function mockIdentifikasi(string $label, float $skor): void
    {
        $this->mock(VerifikasiOtomatisService::class, function ($mock) use ($label, $skor) {
            $mock->shouldReceive('identifikasi')->andReturn(['label' => $label, 'skor' => $skor]);
        });
    }

    /** Kategori yang diterima lokasi uji. */
    protected function lokasiMenerima(KategoriSampah ...$kategori): void
    {
        $this->lokasi->kategoriDiterima()->sync(collect($kategori)->pluck('id_kategori')->all());
    }
}
