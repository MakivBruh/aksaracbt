<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            // null = belum dijawab, A/B/C/D/E = pilihan peserta
            $table->char('jawaban', 1)->nullable();
            $table->timestamp('dijawab_at')->nullable();
            // Satu jawaban per soal per peserta
            $table->unique(['peserta_id', 'soal_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawabans');
    }
};
