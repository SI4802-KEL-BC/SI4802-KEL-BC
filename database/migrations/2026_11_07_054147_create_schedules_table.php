<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pengangkutan', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->foreignId('id_kategori')->constrained('kategori_sampah', 'id_kategori')->restrictOnDelete();
            $table->string('pola', 15);
            $table->unsignedSmallInteger('interval_hari')->nullable();
            $table->unsignedSmallInteger('hari_ke')->nullable();
            $table->string('hari', 10)->nullable();
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->time('jam_buang_mulai');
            $table->time('jam_buang_selesai');
            $table->time('jam_angkut');
            $table->string('catatan', 255)->nullable();
            $table->boolean('kirim_pengingat')->default(true);
            $table->boolean('semua_area')->default(false);
            $table->string('status', 15)->default('aktif')->index();
            $table->timestamps();

            $table->index(['id_kategori', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pengangkutan');
    }
};
