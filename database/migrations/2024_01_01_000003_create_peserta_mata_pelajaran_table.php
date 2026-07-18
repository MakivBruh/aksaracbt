<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: menyimpan 2 mapel PILIHAN tiap peserta.
        // Mapel WAJIB tidak perlu disimpan di sini — langsung di-query via tipe='wajib'.
        Schema::create('peserta_mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->unique(['peserta_id', 'mata_pelajaran_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_mata_pelajaran');
    }
};
