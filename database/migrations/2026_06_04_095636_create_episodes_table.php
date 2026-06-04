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
            // Khóa ngoại nối với bảng movies, xóa phim thì tập phim cũng bay màu
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Tên tập (VD: Tập 1, Tập Full)
            $table->text('stream_url'); // Link m3u8 hoặc Iframe
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};