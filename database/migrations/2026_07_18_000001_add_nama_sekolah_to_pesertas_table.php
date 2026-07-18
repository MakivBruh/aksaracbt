<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pesertas', 'nama_sekolah')) {
            Schema::table('pesertas', function (Blueprint $table) {
                $table->string('nama_sekolah')->nullable()->after('nama');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pesertas', 'nama_sekolah')) {
            Schema::table('pesertas', function (Blueprint $table) {
                $table->dropColumn('nama_sekolah');
            });
        }
    }
};
