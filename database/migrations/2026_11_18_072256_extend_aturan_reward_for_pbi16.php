<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aturan_reward', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->string('kode_aturan', 10)->nullable()->unique();
            $table->string('nama_aturan', 100)->nullable();
            $table->string('jenis_aturan', 20)->default('Penambahan');
            $table->string('satuan', 20)->default('per item');
            $table->json('syarat')->nullable();
            $table->date('berlaku_mulai')->nullable();
            $table->unsignedInteger('batas_per_hari')->nullable()->change();
            $table->integer('poin_per_satuan')->change();
        });

        foreach (DB::table('aturan_reward')->orderBy('id_aturan')->get() as $aturan) {
            DB::table('aturan_reward')->where('id_aturan', $aturan->id_aturan)->update([
                'kode_aturan' => 'RWD-'.str_pad((string) $aturan->id_aturan, 2, '0', STR_PAD_LEFT),
                'nama_aturan' => DB::table('kategori_sampah')->where('id_kategori', $aturan->id_kategori)->value('nama_kategori')
                    ? 'Pemilahan '.DB::table('kategori_sampah')->where('id_kategori', $aturan->id_kategori)->value('nama_kategori')
                    : 'Aturan reward '.$aturan->id_aturan,
            ]);
        }

        Schema::table('aturan_reward', function (Blueprint $table) {
            $table->string('kode_aturan', 10)->nullable(false)->change();
            $table->string('nama_aturan', 100)->nullable(false)->change();
        });

        Schema::table('aturan_reward', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kategori')->nullable()->change();
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori_sampah')->nullOnDelete();
        });

        Schema::table('transaksi_poin', function (Blueprint $table) {
            $table->unsignedBigInteger('id_aturan')->nullable()->after('id_aktivitas');
            $table->foreign('id_aturan')->references('id_aturan')->on('aturan_reward')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_poin', function (Blueprint $table) {
            $table->dropForeign(['id_aturan']);
            $table->dropColumn('id_aturan');
        });

        $kategoriPertama = DB::table('kategori_sampah')->value('id_kategori');
        if ($kategoriPertama !== null) {
            DB::table('aturan_reward')->whereNull('id_kategori')->update(['id_kategori' => $kategoriPertama]);
        }

        Schema::table('aturan_reward', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->unsignedBigInteger('id_kategori')->nullable(false)->change();
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori_sampah');
            $table->dropUnique(['kode_aturan']);
            $table->dropColumn(['kode_aturan', 'nama_aturan', 'jenis_aturan', 'satuan', 'syarat', 'berlaku_mulai']);
            $table->unsignedInteger('poin_per_satuan')->change();
        });
    }
};
