<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Struktur SENGAJA mirroring tabel `soals` di peserta_db, PLUS
        // kolom kunci_jawaban yang cuma ada di sini. mata_pelajaran_id
        // di sini mengacu ke ID mata_pelajarans di peserta_db (dibaca
        // via koneksi 'peserta_db', model MataPelajaran) — TIDAK ada
        // foreign key constraint fisik ke tabel itu karena beda database.
        if (Schema::connection('admin_db')->hasTable('soals')) {
            if (! Schema::connection('admin_db')->hasColumn('soals', 'kunci_jawaban')) {
                Schema::connection('admin_db')->table('soals', function (Blueprint $table) {
                    $table->char('kunci_jawaban', 1)->after('tipe_opsi');
                });
            }

            return;
        }

        Schema::connection('admin_db')->create('soals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mata_pelajaran_id'); // FK logis ke peserta_db.mata_pelajarans
            $table->unsignedSmallInteger('nomor_urut');

            $table->text('teks_soal')->nullable();
            $table->string('gambar_soal')->nullable();

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

            $table->enum('tipe_opsi', ['teks', 'gambar', 'campuran'])->default('teks');

            // ⚠️ HANYA ADA DI ADMIN-APP — jangan pernah muncul di peserta_db
            $table->char('kunci_jawaban', 1);

            $table->unique(['mata_pelajaran_id', 'nomor_urut']);
            $table->index('mata_pelajaran_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('admin_db')->dropIfExists('soals');
    }
};
