<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_pemilahan', function (Blueprint $table) {
            $table->id('id_aktivitas');
            $table->unsignedBigInteger('id_pengguna');
            // Nullable: label model yang tak terpetakan di config belum punya kategori (hasil Ragu).
            $table->unsignedBigInteger('id_kategori')->nullable();
            $table->unsignedBigInteger('id_lokasi');
            $table->unsignedBigInteger('id_sesi')->unique();
            $table->dateTime('tanggal_waktu');
            $table->decimal('jumlah', 10, 2)->default(1);
            $table->string('foto_bukti');
            $table->string('catatan')->nullable();
            $table->string('status_verifikasi', 20); // Menunggu Validasi / Terverifikasi / Ditolak
            $table->string('alasan_penolakan')->nullable();
            $table->integer('poin_diperoleh')->default(0);

            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna');
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori_sampah');
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi_tempat_sampah');
            $table->foreign('id_sesi')->references('id_sesi')->on('sesi_pembuangan');
        });

        // FK transaksi_poin.id_aktivitas (kolom dibuat di migration STUB PBI-13).
        Schema::table('transaksi_poin', function (Blueprint $table) {
            $table->foreign('id_aktivitas')->references('id_aktivitas')->on('aktivitas_pemilahan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_poin', function (Blueprint $table) {
            $table->dropForeign(['id_aktivitas']);
        });
        Schema::dropIfExists('aktivitas_pemilahan');
    }
};
