<?php

namespace Database\Seeders;

use App\Models\LabUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建管理员用户
        $admin = LabUser::where('account', 'admin@example.com')->first();
        if (!$admin) {
            LabUser::create([
                'account' => 'admin@example.com',
                'username' => '管理员',
                'email' => 'admin@example.com',
                'password_hash' => bcrypt('password'),
                'is_active' => 1,
                'role' => 2,
            ]);
            $this->command->info('管理员用户创建成功');
        } else {
            $this->command->info('管理员用户已存在');
        }
    }
}