<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('ophim_id')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->string('origin_name')->nullable();
            $table->text('content')->nullable();
            $table->string('type')->default('single');
            $table->string('thumb_url')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->string('quality')->nullable();
            $table->string('lang')->nullable();
            $table->integer('year')->nullable();
            $table->string('episode_current')->nullable();
            $table->string('episode_total')->nullable();
            $table->string('time')->nullable();
            $table->json('actor')->nullable();
            $table->json('director')->nullable();
            $table->json('country')->nullable();
            $table->integer('view_count')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('last_synced_at')->nullable();

            // Đổi tmdb_id thành nullable
            $table->integer('tmdb_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'ophim_id', 'name', 'slug', 'origin_name', 'content', 'type',
                'thumb_url', 'poster_url', 'trailer_url', 'quality', 'lang',
                'year', 'episode_current', 'episode_total', 'time',
                'actor', 'director', 'country', 'view_count', 'status', 'last_synced_at'
            ]);
            $table->integer('tmdb_id')->unique()->change();
        });
    }
};