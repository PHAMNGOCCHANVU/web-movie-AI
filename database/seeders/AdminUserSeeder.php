<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('services.admin_seed.email'));
        $password = (string) config('services.admin_seed.password');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'Bo qua tao admin vi ADMIN_EMAIL hoac ADMIN_PASSWORD chua duoc cau hinh.'
            );

            return;
        }

        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('services.admin_seed.name', 'CineON Admin'),
                'password' => $password,
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
                'is_locked' => false,
            ]
        );

        $this->command?->info("Tai khoan admin {$email} da san sang.");
    }
}
