<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('book.user.email')->label('用户')->searchable(),
                TextColumn::make('type')->label('类型')->badge()
                    ->color(fn (string $state): string => $state === 'income' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state): string => $state === 'income' ? '收入' : '支出'),
                TextColumn::make('amount')->label('金额')->money('CNY')->sortable(),
                TextColumn::make('category.name')->label('分类'),
                TextColumn::make('occurred_at')->label('时间')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('note')->label('备注')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('类型')
                    ->options(['income' => '收入', 'expense' => '支出']),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}