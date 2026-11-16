<?php

namespace Tests\Feature\PBI13;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PBI-13 · migration STUB: down() hanya men-drop tabel yang dibuat oleh up(),
 * tabel yang sudah ada sebelumnya (milik teman) tidak disentuh.
 * Tanpa RefreshDatabase: tiap test memakai SQLite in-memory yang masih kosong.
 */
class MigrationStubTest extends TestCase
{
    private const STUB = ['pengguna', 'lokasi_tempat_sampah', 'kategori_lokasi', 'aturan_reward', 'transaksi_poin'];

    private function migrationStub(): Migration
    {
        return require database_path('migrations/2026_11_06_110000_stub_pbi13_dependensi.php');
    }

    public function test_down_menghapus_semua_tabel_bila_semuanya_dibuat_oleh_stub(): void
    {
        $m = $this->migrationStub();
        $m->up();

        foreach (self::STUB as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), "$tabel seharusnya dibuat");
        }

        $m->down();

        foreach (self::STUB as $tabel) {
            $this->assertFalse(Schema::hasTable($tabel), "$tabel seharusnya di-drop");
        }
        $this->assertFalse(Schema::hasTable('stub_pbi13_tabel'));
    }

    public function test_down_tidak_menghapus_tabel_yang_sudah_ada_sebelumnya(): void
    {
        // Tabel milik teman sudah ada sebelum migration stub dijalankan.
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->string('nama_lengkap');
        });
        Schema::create('lokasi_tempat_sampah', function (Blueprint $table) {
            $table->id('id_lokasi');
        });

        $m = $this->migrationStub();
        $m->up();
        $m->down();

        $this->assertTrue(Schema::hasTable('pengguna'), 'pengguna milik teman tidak boleh di-drop');
        $this->assertTrue(Schema::hasTable('lokasi_tempat_sampah'), 'lokasi milik teman tidak boleh di-drop');
        $this->assertSame(['id_pengguna', 'nama_lengkap'], Schema::getColumnListing('pengguna'));

        foreach (['kategori_lokasi', 'aturan_reward', 'transaksi_poin', 'stub_pbi13_tabel'] as $tabel) {
            $this->assertFalse(Schema::hasTable($tabel), "$tabel buatan stub seharusnya di-drop");
        }
    }

    public function test_down_tanpa_catatan_tidak_menghapus_apa_pun(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
        });

        $this->migrationStub()->down();

        $this->assertTrue(Schema::hasTable('pengguna'));
    }
}
