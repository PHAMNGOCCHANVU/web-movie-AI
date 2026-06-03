<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('tmdb_id')->unique()->nullable(); // Mã gốc của TMDB
            $table->text('description')->nullable(); // nullable() nghĩa là cho phép bỏ trống nếu phim chưa có thông tin
            $table->date('release_date')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->integer('duration')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};