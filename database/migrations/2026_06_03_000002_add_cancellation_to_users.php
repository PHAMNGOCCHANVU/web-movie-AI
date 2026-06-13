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
            // Auto-renew setting của user (default true - tự động gia hạn)
            $table->boolean('auto_renew')->default(true)->after('subscription_status');
            
            // Thời điểm user hủy gói cước
            $table->timestamp('cancelled_at')->nullable()->after('auto_renew');
            
            // Lý do hủy gói (optional, user có thể không nhập)
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            
            // Index để tìm user đã hủy
            $table->index('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn(['auto_renew', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
