<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * STUB PBI-13 (SCRUM-36)
 * Tabel acuan sementara milik PBI lain. Setiap tabel dibungkus hasTable()
 * agar aman bila migration milik teman sudah ada. Hapus tabel yang duplikat
 * saat migration aslinya masuk ke main (lihat "Catatan Stub Dependensi"
 * di docs/PBI-13.md). kategori_sampah TIDAK di-stub (sudah ada, SCRUM-27).
 *
 * Tabel yang benar-benar dibuat di up() dicatat di tabel kecil
 * `stub_pbi13_tabel`, supaya down() hanya men-drop tabel buatan migration ini
 * dan tidak menyentuh tabel yang sudah ada sebelumnya (milik teman).
 */
return new class extends Migration
{
    /** Tabel pencatat tabel yang dibuat oleh migration stub ini. */
    private const PENCATAT = 'stub_pbi13_tabel';

    /** Urutan drop yang aman terhadap foreign key (anak dulu, induk terakhir). */
    private const URUTAN_DROP = ['transaksi_poin', 'aturan_reward', 'kategori_lokasi', 'lokasi_tempat_sampah', 'pengguna'];

    public function up(): void
    {
        $dibuat = [];

        if (! Schema::hasTable('pengguna')) {
            Schema::create('pengguna', function (Blueprint $table) {
                $table->id('id_pengguna');
                $table->string('nama_lengkap', 100);
                $table->string('email_kampus', 100)->unique();
                $table->string('kata_sandi');
                $table->string('peran', 10)->default('User'); // User / Admin
                $table->string('status_akun', 15)->default('aktif');
                $table->timestamps();
            });
            $dibuat[] = 'pengguna';
        }

        if (! Schema::hasTable('lokasi_tempat_sampah')) {
            Schema::create('lokasi_tempat_sampah', function (Blueprint $table) {
                $table->id('id_lokasi');
                $table->string('nama_lokasi', 100);
                $table->string('kode_titik', 10);
                $table->string('area', 50);
                $table->string('kode_perangkat', 30)->unique();
                $table->string('status', 15)->default('aktif'); // aktif / nonaktif
                $table->timestamps();
            });
            $dibuat[] = 'lokasi_tempat_sampah';
        }

        if (! Schema::hasTable('kategori_lokasi')) {
            Schema::create('kategori_lokasi', function (Blueprint $table) {
                $table->unsignedBigInteger('id_kategori');
                $table->unsignedBigInteger('id_lokasi');
                $table->primary(['id_kategori', 'id_lokasi']);
                $table->foreign('id_kategori')->references('id_kategori')->on('kategori_sampah')->cascadeOnDelete();
                $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi_tempat_sampah')->cascadeOnDelete();
            });
            $dibuat[] = 'kategori_lokasi';
        }

        if (! Schema::hasTable('aturan_reward')) {
            Schema::create('aturan_reward', function (Blueprint $table) {
                $table->id('id_aturan');
                $table->unsignedBigInteger('id_kategori');
                $table->unsignedInteger('poin_per_satuan');
                $table->unsignedInteger('batas_per_hari')->nullable();
                $table->string('status', 15)->default('aktif');
                $table->timestamps();
                $table->foreign('id_kategori')->references('id_kategori')->on('kategori_sampah');
            });
            $dibuat[] = 'aturan_reward';
        }

        // id_aktivitas: FK ke aktivitas_pemilahan ditambahkan di migration aktivitas_pemilahan.
        if (! Schema::hasTable('transaksi_poin')) {
            Schema::create('transaksi_poin', function (Blueprint $table) {
                $table->id('id_transaksi');
                $table->unsignedBigInteger('id_pengguna');
                $table->unsignedBigInteger('id_aktivitas')->nullable();
                $table->dateTime('tanggal');
                $table->string('jenis', 20);
                $table->integer('jumlah_poin');
                $table->string('keterangan')->nullable();
                $table->timestamps();
                $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna');
            });
            $dibuat[] = 'transaksi_poin';
        }

        Schema::create(self::PENCATAT, function (Blueprint $table) {
            $table->string('nama_tabel', 64)->primary();
        });
        DB::table(self::PENCATAT)->insert(array_map(fn ($t) => ['nama_tabel' => $t], $dibuat));
    }

    public function down(): void
    {
        // Tanpa catatan (mis. dijalankan sebelum pencatat ada) -> jangan drop apa pun,
        // karena tidak bisa dipastikan tabel mana yang dibuat migration ini.
        if (! Schema::hasTable(self::PENCATAT)) {
            return;
        }

        $dibuat = DB::table(self::PENCATAT)->pluck('nama_tabel')->all();

        foreach (self::URUTAN_DROP as $tabel) {
            if (in_array($tabel, $dibuat, true)) {
                Schema::dropIfExists($tabel);
            }
        }

        Schema::dropIfExists(self::PENCATAT);
    }
};
