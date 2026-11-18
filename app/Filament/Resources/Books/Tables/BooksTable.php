<?php

namespace App\Filament\Resources\Books\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('用户')->searchable(),
                TextColumn::make('name')->label('名称')->searchable(),
                TextColumn::make('currency')->label('货币'),
                TextColumn::make('transactions_count')->counts('transactions')->label('流水笔数'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}