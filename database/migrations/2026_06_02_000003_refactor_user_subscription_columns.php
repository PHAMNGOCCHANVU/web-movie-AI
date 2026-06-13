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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['vip_package_id']);
            $table->dropColumn(['vip_package_id', 'vip_expires_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->after('role_id')
                ->constrained('subscription_plans')
                ->nullOnDelete();
            $table->timestamp('subscription_starts_at')->nullable()->after('subscription_plan_id');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_starts_at');
            $table->string('subscription_status')->default('none')->after('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn([
                'subscription_plan_id',
                'subscription_starts_at',
                'subscription_expires_at',
                'subscription_status',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('vip_package_id')
                ->nullable()
                ->after('role_id')
                ->constrained('vip_packages');
            $table->timestamp('vip_expires_at')->nullable()->after('vip_package_id');
        });
    }
};
