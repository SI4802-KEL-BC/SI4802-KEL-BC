<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lokasi_tempat_sampah', function (Blueprint $table) {
            $table->string('keterangan', 255)->nullable();
            $table->string('fasilitas', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lokasi_tempat_sampah', function (Blueprint $table) {
            $table->dropColumn(['keterangan', 'fasilitas']);
        });
    }
};
