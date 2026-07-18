<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('no_ujian', 20)->unique();       // e.g. TKA-A1B2C3
            $table->string('token_login', 64)->unique();    // token untuk login
            $table->enum('status', ['belum_mulai', 'sedang_ujian', 'selesai'])->default('belum_mulai');
            $table->unsignedSmallInteger('durasi_menit')->default(120);
            $table->timestamp('mulai_ujian_at')->nullable();
            $table->timestamp('selesai_ujian_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
