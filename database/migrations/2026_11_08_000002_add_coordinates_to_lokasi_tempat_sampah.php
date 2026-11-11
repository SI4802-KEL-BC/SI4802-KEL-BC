<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lokasi_tempat_sampah', function (Blueprint $table) {
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->unique('kode_titik');
        });
    }

    public function down(): void
    {
        Schema::table('lokasi_tempat_sampah', function (Blueprint $table) {
            $table->dropUnique('lokasi_tempat_sampah_kode_titik_unique');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
