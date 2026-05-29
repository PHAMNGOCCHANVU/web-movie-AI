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
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // delete_comment, lock_user, update_movie, etc
            $table->string('target_table')->nullable(); // users, comments, movies
            $table->integer('target_id')->nullable(); // ID của record bị tác động
            $table->json('old_values')->nullable(); // Giá trị cũ trước khi thay đổi
            $table->json('new_values')->nullable(); // Giá trị mới sau khi thay đổi
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index để nhanh chóng query hoạt động của admin
            $table->index('admin_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
