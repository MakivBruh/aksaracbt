<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 10)->unique(); // IND, ING, MAW, MAL, FIS, ...
            $table->enum('tipe', ['wajib', 'pilihan']);
            $table->unsignedSmallInteger('jumlah_soal')->default(20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_pelajarans');
    }
};
