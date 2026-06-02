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
        Schema::rename('vip_packages', 'subscription_plans');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('plan_code')->nullable()->unique()->after('id');
            $table->boolean('is_active')->default(true)->after('duration_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['plan_code', 'is_active']);
        });

        Schema::rename('subscription_plans', 'vip_packages');
    }
};
