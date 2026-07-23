<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('peserta_db')->table('soals', function (Blueprint $table) {
            $table->string('tipe_soal', 32)->default('pilihan_ganda')->after('nomor_urut');
            $table->json('tabel_data')->nullable()->after('teks_soal');
            $table->decimal('nilai_maksimum', 8, 4)->nullable()->after('tipe_opsi');
        });

        Schema::connection('admin_db')->table('soals', function (Blueprint $table) {
            $table->string('tipe_soal', 32)->default('pilihan_ganda')->after('nomor_urut');
            $table->json('tabel_data')->nullable()->after('teks_soal');
            $table->decimal('nilai_maksimum', 8, 4)->nullable()->after('tipe_opsi');
        });

        Schema::connection('admin_db')->create('question_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('soal_id');
            $table->text('konten');
            $table->string('gambar')->nullable();
            $table->boolean('is_correct');
            $table->unsignedSmallInteger('urutan');
            $table->unique(['soal_id', 'urutan']);
            $table->index('soal_id');
            $table->timestamps();
        });

        Schema::connection('peserta_db')->create('question_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            $table->text('konten');
            $table->string('gambar')->nullable();
            $table->unsignedSmallInteger('urutan');
            $table->unique(['soal_id', 'urutan']);
            $table->timestamps();
        });

        Schema::connection('peserta_db')->table('jawabans', function (Blueprint $table) {
            $table->json('jawaban_data')->nullable()->after('jawaban');
        });

        Schema::connection('peserta_db')->table('nilai_details', function (Blueprint $table) {
            $table->decimal('poin_mentah', 10, 6)->default(0)->after('skor');
        });

        Schema::connection('peserta_db')->create('nilai_totals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->unique()->constrained('pesertas')->cascadeOnDelete();
            $table->decimal('poin_mentah', 10, 6)->default(0);
            $table->decimal('nilai_akhir', 8, 6)->default(0);
            $table->timestamps();
        });

        Schema::connection('peserta_db')->create('nilai_soal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            $table->decimal('poin_diperoleh', 10, 6)->default(0);
            $table->decimal('poin_maksimum', 10, 6)->default(0);
            $table->unsignedSmallInteger('sub_item_benar')->default(0);
            $table->unsignedSmallInteger('jumlah_sub_item')->default(1);
            $table->unique(['peserta_id', 'soal_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('peserta_db')->dropIfExists('nilai_soal_details');
        Schema::connection('peserta_db')->dropIfExists('nilai_totals');
        Schema::connection('peserta_db')->table('nilai_details', function (Blueprint $table) {
            $table->dropColumn('poin_mentah');
        });
        Schema::connection('peserta_db')->table('jawabans', function (Blueprint $table) {
            $table->dropColumn('jawaban_data');
        });
        Schema::connection('peserta_db')->dropIfExists('question_items');
        Schema::connection('admin_db')->dropIfExists('question_items');
        Schema::connection('admin_db')->table('soals', function (Blueprint $table) {
            $table->dropColumn(['tipe_soal', 'tabel_data', 'nilai_maksimum']);
        });
        Schema::connection('peserta_db')->table('soals', function (Blueprint $table) {
            $table->dropColumn(['tipe_soal', 'tabel_data', 'nilai_maksimum']);
        });
    }
};
