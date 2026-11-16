<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titik_lokasi', function (Blueprint $t) {
            $t->id();
            $t->string('nama_lokasi', 100);
            $t->string('kode_titik', 10)->unique();
            $t->string('area', 50);
            $t->string('keterangan', 255)->nullable();
            $t->string('kode_perangkat', 20)->unique();
            $t->decimal('latitude', 9, 6);
            $t->decimal('longitude', 9, 6);
            $t->string('fasilitas', 255)->nullable();
            $t->string('status', 15)->default('aktif');
            $t->timestamps();
        });

        Schema::create('kategori_titik', function (Blueprint $t) {
            $t->id();
            $t->string('nama_kategori', 50)->unique();
            $t->string('kode', 10)->unique();
            $t->string('warna_label', 20)->nullable();
            $t->timestamps();
        });

        Schema::create('titik_lokasi_kategori', function (Blueprint $t) {
            $t->foreignId('titik_lokasi_id')->constrained('titik_lokasi')->cascadeOnDelete();
            $t->foreignId('kategori_titik_id')->constrained('kategori_titik')->cascadeOnDelete();
            $t->primary(['titik_lokasi_id', 'kategori_titik_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titik_lokasi_kategori');
        Schema::dropIfExists('kategori_titik');
        Schema::dropIfExists('titik_lokasi');
    }
};
