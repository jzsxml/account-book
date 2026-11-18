<?php

namespace App\Filament\Resources\UserLoginLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserLoginLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label('昵称'),
                TextEntry::make('user.email')->label('邮箱'),
                TextEntry::make('ip_address')->label('IP'),
                TextEntry::make('user_agent')->label('User-Agent')->columnSpanFull(),
                TextEntry::make('logged_in_at')->label('登录时间')->dateTime('Y-m-d H:i:s'),
            ]);
    }
}