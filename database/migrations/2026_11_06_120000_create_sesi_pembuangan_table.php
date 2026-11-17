<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_pembuangan', function (Blueprint $table) {
            $table->id('id_sesi');
            $table->unsignedBigInteger('id_pengguna');
            $table->unsignedBigInteger('id_lokasi');
            $table->string('token_qr', 64)->unique(); // hash sha256 token QR (cegah pakai ulang)
            $table->string('status_sesi', 15); // Aktif / Selesai / Kedaluwarsa
            $table->dateTime('waktu_scan');
            $table->dateTime('kedaluwarsa_pada');

            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna');
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi_tempat_sampah');
            $table->index(['status_sesi', 'kedaluwarsa_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_pembuangan');
    }
};
