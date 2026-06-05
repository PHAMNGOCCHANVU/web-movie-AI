<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('vnp_response_code')->nullable()->after('vnp_transaction_no');
            $table->string('vnp_bank_code')->nullable()->after('vnp_response_code');
            $table->text('vnp_secure_hash')->nullable()->after('vnp_bank_code');
            $table->string('order_info')->nullable()->after('vnp_secure_hash');
            $table->timestamp('vnp_pay_date')->nullable()->after('order_info');
            $table->string('ip_address')->nullable()->after('vnp_pay_date');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'vnp_response_code', 'vnp_bank_code', 'vnp_secure_hash',
                'order_info', 'vnp_pay_date', 'ip_address'
            ]);
        });
    }
};