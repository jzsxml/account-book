<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('昵称'),
                TextEntry::make('email')->label('邮箱')->copyable(),
                TextEntry::make('email_verified_at')->label('邮箱验证')->dateTime('Y-m-d H:i')->placeholder('未验证'),
                TextEntry::make('banned_at')->label('封号时间')->dateTime('Y-m-d H:i')->placeholder('正常'),
                TextEntry::make('banned_reason')->label('封号原因')->placeholder('—'),
                TextEntry::make('books.name')->label('账本')->badge(),
                TextEntry::make('created_at')->label('注册时间')->dateTime('Y-m-d H:i'),
            ]);
    }
}