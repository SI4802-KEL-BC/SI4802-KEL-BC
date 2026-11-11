<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panduan_pemilahan', function (Blueprint $table) {
            $table->id('id_panduan');
            $table->foreignId('id_kategori')
                ->constrained('kategori_sampah', 'id_kategori')
                ->restrictOnDelete();
            $table->string('judul', 150);
            $table->string('slug', 180)->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('langkah_pemilahan');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panduan_pemilahan');
    }
};
