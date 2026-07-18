<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini diisi oleh admin-app setelah proses scoring.
        // Memungkinkan laporan "peserta A: Fisika 8 benar, Kimia 6 benar" dst.
        Schema::create('nilai_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->unsignedSmallInteger('benar')->default(0);
            $table->unsignedSmallInteger('salah')->default(0);
            $table->unsignedSmallInteger('kosong')->default(0);
            $table->decimal('skor', 6, 2)->default(0);
            $table->unique(['peserta_id', 'mata_pelajaran_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_details');
    }
};
