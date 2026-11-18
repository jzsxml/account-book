<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('昵称')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn ($livewire): bool => $livewire instanceof CreateUser)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn ($livewire): ?string => $livewire instanceof CreateUser
                        ? null
                        : '留空表示不修改密码'),
                TextInput::make('password_confirmation')
                    ->label('确认密码')
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->required(fn ($livewire): bool => $livewire instanceof CreateUser)
                    ->dehydrated(false)
                    ->visible(fn ($livewire): bool => $livewire instanceof CreateUser
                        || filled($livewire->data['password'] ?? null)),
                Toggle::make('email_verified')
                    ->label('邮箱已验证')
                    ->helperText('开启后用户可直接登录，无需邮件验证。')
                    ->default(true),
            ]);
    }
}