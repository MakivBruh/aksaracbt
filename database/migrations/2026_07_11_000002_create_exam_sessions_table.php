<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('pesertas', function (Blueprint $table) {
            $table->foreignId('active_exam_session_id')
                ->nullable()
                ->after('active_session_token')
                ->constrained('exam_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_exam_session_id');
        });

        Schema::dropIfExists('exam_sessions');
    }
};
