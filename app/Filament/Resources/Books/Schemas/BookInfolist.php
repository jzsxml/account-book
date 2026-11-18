<?php

namespace App\Filament\Resources\Books\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BookInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.email')->label('用户'),
                TextEntry::make('name')->label('名称'),
                TextEntry::make('currency')->label('货币'),
                TextEntry::make('timezone')->label('时区'),
                TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i'),
            ]);
    }
}