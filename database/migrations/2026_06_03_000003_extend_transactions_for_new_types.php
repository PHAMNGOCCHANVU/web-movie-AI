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
            // Lưu lý do giao dịch chi tiết hơn
            $table->text('description')->nullable()->after('transaction_type');
            
            // Đánh dấu đây có phải auto-renewal không
            $table->boolean('is_auto_renewal')->default(false)->after('description');
            
            // Nếu hủy/downgrade, lưu plan_id cũ để có history
            $table->foreignId('previous_subscription_plan_id')
                ->nullable()
                ->after('is_auto_renewal')
                ->constrained('subscription_plans')
                ->nullOnDelete();
            
            // Lưu billing cycle được chọn (monthly/yearly)
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->after('previous_subscription_plan_id');
            
            // Index để tìm auto-renewal transactions
            $table->index('is_auto_renewal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['is_auto_renewal']);
            $table->dropForeign(['transactions_previous_subscription_plan_id_foreign']);
            $table->dropColumn(['description', 'is_auto_renewal', 'previous_subscription_plan_id', 'billing_cycle']);
        });
    }
};
