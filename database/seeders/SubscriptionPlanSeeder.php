<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'plan_code' => 'standard',
                'name' => 'Gói Tiêu chuẩn (Standard)',
                'price' => 79000,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'plan_code' => 'vip',
                'name' => 'Gói Cao cấp (VIP)',
                'price' => 149000,
                'duration_days' => 30,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['plan_code' => $plan['plan_code']],
                array_merge($plan, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
