<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->index(['status', 'no_ujian'], 'pesertas_status_no_ujian_idx');
            $table->index(['status', 'selesai_ujian_at'], 'pesertas_status_selesai_idx');
        });

        Schema::table('log_pelanggarans', function (Blueprint $table) {
            $table->index(['peserta_id', 'terjadi_at'], 'log_pelanggaran_peserta_waktu_idx');
            $table->index(['peserta_id', 'tipe'], 'log_pelanggaran_peserta_tipe_idx');
        });

        Schema::table('nilai_details', function (Blueprint $table) {
            $table->index(['mata_pelajaran_id', 'peserta_id'], 'nilai_details_mapel_peserta_idx');
        });

        Schema::table('peserta_mata_pelajaran', function (Blueprint $table) {
            $table->index(['mata_pelajaran_id', 'peserta_id'], 'pmp_mapel_peserta_idx');
        });
    }

    public function down(): void
    {
        Schema::table('peserta_mata_pelajaran', function (Blueprint $table) {
            $table->dropIndex('pmp_mapel_peserta_idx');
        });

        Schema::table('nilai_details', function (Blueprint $table) {
            $table->dropIndex('nilai_details_mapel_peserta_idx');
        });

        Schema::table('log_pelanggarans', function (Blueprint $table) {
            $table->dropIndex('log_pelanggaran_peserta_waktu_idx');
            $table->dropIndex('log_pelanggaran_peserta_tipe_idx');
        });

        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropIndex('pesertas_status_no_ujian_idx');
            $table->dropIndex('pesertas_status_selesai_idx');
        });
    }
};
