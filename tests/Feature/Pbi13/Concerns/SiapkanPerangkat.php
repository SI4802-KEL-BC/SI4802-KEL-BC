<?php

namespace Tests\Feature\Pbi13\Concerns;

use App\Models\KategoriSampah;
use App\Models\LokasiTempatSampah;
use App\Models\Pengguna;
use App\Services\QrTokenService;
use App\Services\VerifikasiOtomatisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/** Pembantu bersama untuk test API perangkat PBI-13. */
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

    protected function buatQr(?Pengguna $pengguna = null): string
    {
        return app(QrTokenService::class)->buat($pengguna ?? $this->pengguna)['token'];
    }

    protected function scan(string $token, ?string $kodePerangkat = null, ?array $headers = null): TestResponse
    {
        return $this->postJson('/api/perangkat/scan', [
            'token_qr' => $token,
            'kode_perangkat' => $kodePerangkat ?? $this->lokasi->kode_perangkat,
        ], $headers ?? $this->headerPerangkat());
    }

    protected function kirimFoto(string $token, ?array $headers = null, ?UploadedFile $foto = null): TestResponse
    {
        return $this->postJson('/api/perangkat/foto', [
            'token_qr' => $token,
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
