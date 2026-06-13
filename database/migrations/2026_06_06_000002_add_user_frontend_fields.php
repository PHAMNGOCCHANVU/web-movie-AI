<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
        });

        Schema::table('movie_user', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->default(0)->after('watch_progress_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('movie_user', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
