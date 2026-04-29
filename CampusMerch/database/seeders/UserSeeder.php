<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 普通用户（用于测试注册/登录）
        User::create([
            'name' => '测试用户',
            'email' => 'test@example.com',
            'password' => '123456',   // 模型自动哈希
            'role' => 'user',
        ]);

        // 管理员（用于之后管理员接口测试）
        User::create([
            'name' => '管理员',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin',
        ]);
    }
}
