<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('book.user.email')->label('用户'),
                TextEntry::make('name')->label('名称'),
                TextEntry::make('type')->label('类型')
                    ->formatStateUsing(fn (string $state): string => $state === 'income' ? '收入' : '支出'),
                TextEntry::make('icon')->label('图标'),
            ]);
    }
}