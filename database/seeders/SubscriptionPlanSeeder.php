<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Tạo 4 gói cước: Standard/VIP × Monthly/Yearly
     * Gói yearly có chiết khấu 20%
     */
    public function run(): void
    {
        DB::table('subscription_plans')->upsert(
            [
                // Standard Monthly Plan - 49,000 VND/tháng
                [
                    'plan_code' => 'standard_monthly',
                    'name' => 'Standard - Hàng tháng',
                    'price' => 49000,
                    'price_monthly' => 49000,
                    'price_yearly' => null,
                    'duration_days' => 30,
                    'billing_cycle_type' => 'monthly',
                    'yearly_discount_percent' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // Standard Yearly Plan - 470,400 VND/năm (20% off: 49000*12*0.8)
                [
                    'plan_code' => 'standard_yearly',
                    'name' => 'Standard - Hàng năm',
                    'price' => 470400,
                    'price_monthly' => null,
                    'price_yearly' => 470400,
                    'duration_days' => 365,
                    'billing_cycle_type' => 'yearly',
                    'yearly_discount_percent' => 20,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // VIP Monthly Plan - 99,000 VND/tháng
                [
                    'plan_code' => 'vip_monthly',
                    'name' => 'VIP - Hàng tháng',
                    'price' => 99000,
                    'price_monthly' => 99000,
                    'price_yearly' => null,
                    'duration_days' => 30,
                    'billing_cycle_type' => 'monthly',
                    'yearly_discount_percent' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // VIP Yearly Plan - 950,400 VND/năm (20% off: 99000*12*0.8)
                [
                    'plan_code' => 'vip_yearly',
                    'name' => 'VIP - Hàng năm',
                    'price' => 950400,
                    'price_monthly' => null,
                    'price_yearly' => 950400,
                    'duration_days' => 365,
                    'billing_cycle_type' => 'yearly',
                    'yearly_discount_percent' => 20,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            // Unique key - upsert sẽ update nếu tồn tại
            ['plan_code'],
            // Columns to update
            ['name', 'price', 'price_monthly', 'price_yearly', 'duration_days', 'billing_cycle_type', 'yearly_discount_percent', 'updated_at']
        );
    }
}
