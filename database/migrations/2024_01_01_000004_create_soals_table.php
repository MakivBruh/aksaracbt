<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CATATAN KEAMANAN:
        // Tabel ini TIDAK menyimpan kunci_jawaban.
        // Kunci jawaban disimpan terpisah di admin-app database,
        // sehingga tidak bisa diakses walau database peserta-app bocor.
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor_urut');

            // Teks soal (boleh berisi sintaks LaTeX: $...$ atau $$...$$)
            $table->text('teks_soal')->nullable();
            // Gambar soal (path relatif di storage, nama file UUID)
            $table->string('gambar_soal')->nullable();

            // Opsi jawaban — teks (boleh LaTeX) + gambar masing-masing
            $table->text('opsi_a')->nullable();
            $table->text('opsi_b')->nullable();
            $table->text('opsi_c')->nullable();
            $table->text('opsi_d')->nullable();
            $table->text('opsi_e')->nullable();

            $table->string('gambar_opsi_a')->nullable();
            $table->string('gambar_opsi_b')->nullable();
            $table->string('gambar_opsi_c')->nullable();
            $table->string('gambar_opsi_d')->nullable();
            $table->string('gambar_opsi_e')->nullable();

            // Menentukan cara render opsi di frontend
            $table->enum('tipe_opsi', ['teks', 'gambar', 'campuran'])->default('teks');

            $table->unique(['mata_pelajaran_id', 'nomor_urut']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soals');
    }
};
