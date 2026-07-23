<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admin_db', 'peserta_db'] as $connection) {
            Schema::connection($connection)->table('soals', function (Blueprint $table) {
                $table->string('option_label_a', 80)->default('Benar')->after('tipe_soal');
                $table->string('option_label_b', 80)->default('Salah')->after('option_label_a');
                $table->longText('teks_soal')->nullable()->change();
            });
        }
        Schema::connection('admin_db')->table('question_items', function (Blueprint $table) {
            $table->char('correct_value', 1)->nullable()->after('is_correct');
        });
        DB::connection('admin_db')->table('question_items')->orderBy('id')->chunkById(500, function ($items) {
            foreach ($items as $item) {
                DB::connection('admin_db')->table('question_items')->where('id', $item->id)
                    ->update(['correct_value' => $item->is_correct ? 'A' : 'B']);
            }
        });
    }

    public function down(): void
    {
        Schema::connection('admin_db')->table('question_items', fn (Blueprint $table) => $table->dropColumn('correct_value'));
        foreach (['admin_db', 'peserta_db'] as $connection) {
            Schema::connection($connection)->table('soals', fn (Blueprint $table) => $table->dropColumn(['option_label_a', 'option_label_b']));
        }
    }
};
