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
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Hành động: purchased, renewed, upgraded, downgraded, cancelled, expired
            $table->enum('action', ['purchased', 'renewed', 'upgraded', 'downgraded', 'cancelled', 'expired']);
            
            // Gói cước hiện tại
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            
            // Gói cước cũ (khi upgrade/downgrade/cancel)
            $table->foreignId('previous_subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            
            // Số tiền giao dịch liên quan
            $table->decimal('amount', 12, 2)->nullable();
            
            // Chu kỳ thanh toán: monthly/yearly
            $table->enum('billing_cycle', ['monthly', 'yearly'])->nullable();
            
            // Lý do thay đổi (e.g., "User cancelled", "Upgrade to VIP", "Payment failed")
            $table->text('reason')->nullable();
            
            // Ngày bắt đầu/kết thúc subscription tương ứng
            $table->timestamp('subscription_starts_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            
            $table->timestamps();
            
            // Index để tìm kiếm nhanh theo user và action
            $table->index(['user_id', 'action']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
