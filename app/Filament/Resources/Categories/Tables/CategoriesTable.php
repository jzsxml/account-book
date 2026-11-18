<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('book.user.email')->label('用户'),
                TextColumn::make('book.name')->label('账本'),
                TextColumn::make('name')->label('分类'),
                TextColumn::make('type')->label('类型')
                    ->formatStateUsing(fn (string $state): string => $state === 'income' ? '收入' : '支出'),
                TextColumn::make('icon')->label('图标'),
            ])
            ->filters([
                SelectFilter::make('type')->options(['income' => '收入', 'expense' => '支出']),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}