<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BookProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public const EMAIL = 'test@example.com';

    public const PASSWORD = 'password';

    public function run(BookProvisioner $provisioner): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => '测试用户',
                'password' => Hash::make(self::PASSWORD),
                'settings' => ['timezone' => 'Asia/Shanghai'],
                'email_verified_at' => now(),
            ]
        );

        if ($user->books()->doesntExist()) {
            $provisioner->provisionForUser($user);
        } elseif (! ($user->settings['default_book_id'] ?? null)) {
            $book = $user->books()->orderBy('id')->first();
            $settings = $user->settings ?? [];
            $settings['default_book_id'] = $book->id;
            $user->settings = $settings;
            $user->save();
        }

        $this->command?->info('测试用户已就绪');
        $this->command?->line('邮箱: '.self::EMAIL);
        $this->command?->line('密码: '.self::PASSWORD);
    }
}