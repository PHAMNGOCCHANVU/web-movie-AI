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
        Schema::create('ai_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message'); // Câu hỏi/tin nhắn từ user
            $table->text('response'); // Phản hồi từ AI
            $table->json('context')->nullable(); // Dữ liệu context (phim được tìm, filter, v.v.)
            $table->timestamps();
            
            // Index để nhanh chóng query lịch sử của user
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_conversations');
    }
};
