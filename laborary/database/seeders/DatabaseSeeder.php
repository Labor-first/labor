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
        // 创建或更新李鑫管理员用户
        $users = [
            [
                'account' => 'lixin',
                'username' => '李鑫',
                'phone' => '13800000000',
                'email' => '3258599349@qq.com',
                'password_hash' => Hash::make('123456'),
                'is_active' => 0,
                'role' => 1,
                'department_id' => null,
            ],
            [
                'account' => 'wangjiachang',
                'username' => '王佳畅',
                'phone' => '18400000000',
                'email' => 'wjc20070117@qq.com',
                'password_hash' => Hash::make('123456'),
                'is_active' => 1,
                'role' => 1,
                'department_id' => null,
            ],
            [
                'account' => 'fumingyue',
                'username' => '伏明月',
                'phone' => '18500000000',
                'email' => '3227605507@qq.com',
                'password_hash' => Hash::make('123456'),
                'is_active' => 0,
                'role' => 1,
                'department_id' => null,
            ],
            [
                'account' => 'xujie',
                'username' => '徐杰',
                'phone' => '13900000000',
                'email' => '1096786713@qq.com',
                'password_hash' => Hash::make('123456'),
                'is_active' => 0,
                'role' => 1,
                'department_id' => null,
            ],
        ];

        foreach ($users as $userData) {
            // 先检查邮箱是否已存在
            $existingUser = LabUser::where('email', $userData['email'])->first();
            if ($existingUser) {
                // 如果邮箱已存在，更新账号
                $existingUser->update([
                    'account' => $userData['account'],
                    'username' => $userData['username'],
                    'phone' => $userData['phone'],
                    'password_hash' => $userData['password_hash'],
                    'is_active' => $userData['is_active'],
                    'role' => $userData['role'],
                    'department_id' => $userData['department_id'],
                ]);
            } else {
                // 邮箱不存在，创建新用户
                LabUser::create($userData);
            }
        }
    }
}
