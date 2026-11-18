<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@localhost';

    public const PASSWORD = 'admin123456';

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => '系统管理员',
                'password' => Hash::make(self::PASSWORD),
                'is_admin' => true,
                'email_verified_at' => now(),
                'banned_at' => null,
                'banned_reason' => null,
                'settings' => ['timezone' => 'Asia/Shanghai'],
            ]
        );

        $this->command?->info('管理员账号已就绪（仅可登录 /admin）');
        $this->command?->line('邮箱: '.self::EMAIL);
        $this->command?->line('密码: '.self::PASSWORD);
    }
}