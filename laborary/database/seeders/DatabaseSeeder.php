<?php

namespace Database\Seeders;

use App\Models\LabUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 创建李鑫管理员用户
        LabUser::create([
            'account' => 'lixin',
            'username' => '李鑫',
            'phone' => '18411111111',
            'email' => 'lixin@example.com',
            'password_hash' => Hash::make('123456'),
            'is_active' => 1,
            'role' => 1,
            'department_id' => null,
        ]);
        LabUser::create([
            'account' => 'wangjiachang',
            'username' => '王佳畅',
            'phone' => '18400000000',
            'email' => 'wjc20070117@qq.com',
            'password_hash' => Hash::make('123456'),
            'is_active' => 1,
            'role' => 1,
            'department_id' => null,
        ]);
    }
}
