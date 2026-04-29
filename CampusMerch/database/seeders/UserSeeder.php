<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 你本地的用户 zhy
        User::create([
            'name' => 'zhy',
            'email' => '3090124130@qq.com',
            'password' => 'newpass123',  // 如 123456
            'role' => 'user',
        ]);

        // 测试用户
        User::create([
            'name' => '测试用户',
            'email' => 'test@example.com',
            'password' => '123456',
            'role' => 'user',
        ]);

        // 管理员
        User::create([
            'name' => '管理员',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin',
        ]);
    }
}
