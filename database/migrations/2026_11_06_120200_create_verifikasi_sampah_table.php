<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_sampah', function (Blueprint $table) {
            $table->id('id_verifikasi');
            $table->unsignedBigInteger('id_aktivitas');
            $table->unsignedBigInteger('id_verifikator')->nullable(); // NULL jika otomatis
            $table->unsignedInteger('tahap'); // 1 = otomatis, 2 = manual
            $table->string('hasil', 15); // Valid / Tidak Valid / Ragu
            $table->decimal('skor_keyakinan', 5, 2)->nullable();
            $table->string('label_terdeteksi', 50)->nullable();
            $table->string('foto_sampah');
            $table->dateTime('waktu_verifikasi');
            $table->string('keterangan')->nullable();

            $table->foreign('id_aktivitas')->references('id_aktivitas')->on('aktivitas_pemilahan')->cascadeOnDelete();
            $table->foreign('id_verifikator')->references('id_pengguna')->on('pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_sampah');
    }
};
