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
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Thêm cột billing_cycle_type để phân biệt tháng/năm
            $table->enum('billing_cycle_type', ['monthly', 'yearly'])->default('monthly')->after('plan_code');
            
            // Tách giá thành monthly/yearly thay vì dùng price chung
            // Dùng CHANGE để update column name nếu cần, hoặc add new columns
            $table->decimal('price_monthly', 12, 2)->nullable()->after('billing_cycle_type');
            $table->decimal('price_yearly', 12, 2)->nullable()->after('price_monthly');
            
            // Phần trăm chiết khấu khi mua năm
            $table->integer('yearly_discount_percent')->default(0)->after('price_yearly');
            
            // Index để tìm kiếm nhanh theo billing_cycle_type
            $table->index('billing_cycle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['billing_cycle_type']);
            $table->dropColumn(['billing_cycle_type', 'price_monthly', 'price_yearly', 'yearly_discount_percent']);
        });
    }
};
