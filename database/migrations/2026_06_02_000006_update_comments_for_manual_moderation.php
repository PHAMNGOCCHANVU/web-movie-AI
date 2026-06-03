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
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('toxic_score');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('is_hidden');
            $table->foreignId('hidden_by')
                ->nullable()
                ->after('hidden_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['hidden_by']);
            $table->dropColumn(['hidden_at', 'hidden_by']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->decimal('toxic_score', 5, 4)->nullable()->after('content');
        });
    }
};
