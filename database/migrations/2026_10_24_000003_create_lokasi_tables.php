<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lokasi_tempat_sampah', function (Blueprint $t) {
            $t->id('id_lokasi');
            $t->string('nama_lokasi', 100);
            $t->string('kode_titik', 10)->unique();
            $t->decimal('latitude', 9, 6);
            $t->decimal('longitude', 9, 6);
            $t->string('status', 15)->default('aktif');
            $t->timestamps();
        });

        // Pivot banyak-ke-banyak kategori <-> lokasi (PK gabungan).
        Schema::create('kategori_lokasi', function (Blueprint $t) {
            $t->foreignId('id_kategori')->constrained('kategori_sampah', 'id_kategori')->cascadeOnDelete();
            $t->foreignId('id_lokasi')->constrained('lokasi_tempat_sampah', 'id_lokasi')->cascadeOnDelete();
            $t->primary(['id_kategori', 'id_lokasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_lokasi');
        Schema::dropIfExists('lokasi_tempat_sampah');
    }
};
