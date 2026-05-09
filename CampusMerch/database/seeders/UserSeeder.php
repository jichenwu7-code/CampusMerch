<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'zhy',
                'email' => '3090124130@qq.com',
                'password' => 'newpass123',       // 你重置后的密码
                'role' => 'user',
                'mobile' => '13800138000',
            ],
            [
                'name' => 'wjc',
                'email' => 'wjc20070117@qq.com',
                'password' => '123456',    // 请替换为她注册时用的密码
                'role' => 'admin',
                'mobile' => null,
            ],
            [
                'name' => 'gyz',
                'email' => '2200417929@qq.com',
                'password' => '032411',    // 请替换为她注册时用的密码
                'role' => 'user',
                'mobile' => null,
            ]
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
