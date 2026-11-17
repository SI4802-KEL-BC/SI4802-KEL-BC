<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table): void {
            $table->string('nama_panggilan', 100)->nullable();
            $table->string('nim_nip', 50)->nullable();
            $table->string('program_studi', 100)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('foto_profil')->nullable();
            $table->date('tanggal_bergabung')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table): void {
            $table->dropColumn([
                'nama_panggilan',
                'nim_nip',
                'program_studi',
                'no_hp',
                'foto_profil',
                'tanggal_bergabung',
            ]);
        });
    }
};
