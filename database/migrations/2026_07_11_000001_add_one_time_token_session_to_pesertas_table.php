<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->string('token_login', 64)->nullable()->change();
            $table->timestamp('token_used_at')->nullable()->after('token_login');
            $table->string('active_session_token', 64)->nullable()->unique()->after('token_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropColumn(['token_used_at', 'active_session_token']);
            $table->string('token_login', 64)->nullable(false)->change();
        });
    }
};
