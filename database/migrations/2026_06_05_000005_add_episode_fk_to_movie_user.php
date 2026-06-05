<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movie_user', function (Blueprint $table) {
            // episode_id hiện tại là integer, cần sửa thành foreignId
            // Drop cột cũ và tạo lại với FK
            $table->dropColumn('episode_id');
        });

        Schema::table('movie_user', function (Blueprint $table) {
            $table->foreignId('episode_id')
                ->nullable()
                ->after('season_id')
                ->constrained('episodes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movie_user', function (Blueprint $table) {
            $table->dropForeign(['episode_id']);
            $table->dropColumn('episode_id');
        });

        Schema::table('movie_user', function (Blueprint $table) {
            $table->integer('episode_id')->nullable()->after('season_id');
        });
    }
};