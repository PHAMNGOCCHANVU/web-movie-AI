<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('server_name');
            $table->string('name');
            $table->string('slug');
            $table->string('filename')->nullable();
            $table->text('link_embed')->nullable();
            $table->text('link_m3u8')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['movie_id', 'server_name']);
            $table->unique(['movie_id', 'server_name', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};