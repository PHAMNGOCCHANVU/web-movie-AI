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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('subscription_plans')
                ->nullOnDelete();
            $table->string('transaction_type')->default('purchase')->after('subscription_plan_id');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['subscription_plan_id', 'transaction_type']);
        });
    }
};
