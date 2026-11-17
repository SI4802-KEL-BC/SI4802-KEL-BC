<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan kolom dengan Design Tabel di dokumen Jira PBI:
 * - PBI-1 pengguna: nim_nip VARCHAR(20) unique, nama_panggilan VARCHAR(50), no_hp VARCHAR(15), peran VARCHAR(20).
 * - PBI-4 kategori_sampah: nama_kategori unique.
 *
 * Migration berhenti dengan pesan jelas bila ada data yang dobel atau terlalu panjang,
 * supaya tidak ada data yang terpotong diam-diam.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->pastikanDataAman();

        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('nama_panggilan', 50)->nullable()->change();
            $table->string('nim_nip', 20)->nullable()->change();
            $table->string('no_hp', 15)->nullable()->change();
            $table->string('peran', 20)->default('User')->change();
            $table->unique('nim_nip');
        });

        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->unique('nama_kategori');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_sampah', function (Blueprint $table) {
            $table->dropUnique(['nama_kategori']);
        });

        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropUnique(['nim_nip']);
            $table->string('nama_panggilan', 100)->nullable()->change();
            $table->string('nim_nip', 50)->nullable()->change();
            $table->string('no_hp', 20)->nullable()->change();
            $table->string('peran', 10)->default('User')->change();
        });
    }

    private function pastikanDataAman(): void
    {
        $masalah = [];

        $nimDobel = DB::table('pengguna')->whereNotNull('nim_nip')
            ->select('nim_nip')->groupBy('nim_nip')->havingRaw('COUNT(*) > 1')->pluck('nim_nip');
        if ($nimDobel->isNotEmpty()) {
            $masalah[] = 'NIM/NIP dobel di tabel pengguna: '.$nimDobel->join(', ');
        }

        $kategoriDobel = DB::table('kategori_sampah')
            ->select('nama_kategori')->groupBy('nama_kategori')->havingRaw('COUNT(*) > 1')->pluck('nama_kategori');
        if ($kategoriDobel->isNotEmpty()) {
            $masalah[] = 'nama_kategori dobel di tabel kategori_sampah: '.$kategoriDobel->join(', ');
        }

        foreach (['nama_panggilan' => 50, 'nim_nip' => 20, 'no_hp' => 15, 'peran' => 20] as $kolom => $maks) {
            $jumlah = DB::table('pengguna')->whereRaw('LENGTH('.$kolom.') > ?', [$maks])->count();
            if ($jumlah > 0) {
                $masalah[] = "{$jumlah} baris pengguna.{$kolom} lebih dari {$maks} karakter";
            }
        }

        if ($masalah !== []) {
            throw new RuntimeException("Migration dibatalkan, rapikan data dulu:\n- ".implode("\n- ", $masalah));
        }
    }
};
