<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->string('moderation_status', 32)
                ->default('approved')
                ->after('content');
            $table->string('moderation_model')->nullable()->after('moderation_status');
            $table->decimal('moderation_score', 6, 5)->nullable()->after('moderation_model');
            $table->json('moderation_categories')->nullable()->after('moderation_score');
            $table->text('moderation_reason')->nullable()->after('moderation_categories');
            $table->timestamp('moderated_at')->nullable()->after('moderation_reason');
            $table->timestamp('reviewed_at')->nullable()->after('moderated_at');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['moderation_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['moderation_status', 'created_at']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'moderation_status',
                'moderation_model',
                'moderation_score',
                'moderation_categories',
                'moderation_reason',
                'moderated_at',
                'reviewed_at',
                'reviewed_by',
            ]);
        });
    }
};
