<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mata_pelajarans')
            ->where('nama', 'Matematika Lanjutan')
            ->update(['nama' => 'Matematika Tingkat Lanjut']);
    }

    public function down(): void
    {
        DB::table('mata_pelajarans')
            ->where('nama', 'Matematika Tingkat Lanjut')
            ->update(['nama' => 'Matematika Lanjutan']);
    }
};
