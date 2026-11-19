<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_lokasi', function (Blueprint $table) {
            $table->foreignId('id_jadwal')->constrained('jadwal_pengangkutan', 'id_jadwal')->cascadeOnDelete();
            $table->foreignId('id_lokasi')->constrained('lokasi_tempat_sampah', 'id_lokasi')->restrictOnDelete();
            $table->primary(['id_jadwal', 'id_lokasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_lokasi');
    }
};
