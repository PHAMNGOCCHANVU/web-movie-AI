<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movie_user', function (Blueprint $table) {
            $table->integer('season_id')->nullable()->after('movie_id');
            $table->integer('episode_id')->nullable()->after('season_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_user', function (Blueprint $table) {
            $table->dropColumn(['season_id', 'episode_id']);
        });
    }
};
