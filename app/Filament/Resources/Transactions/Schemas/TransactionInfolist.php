<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('book.user.email')->label('用户'),
                TextEntry::make('book.name')->label('账本'),
                TextEntry::make('type')->label('类型')
                    ->formatStateUsing(fn (string $state): string => $state === 'income' ? '收入' : '支出'),
                TextEntry::make('amount')->label('金额')->money('CNY'),
                TextEntry::make('category.name')->label('分类'),
                TextEntry::make('occurred_at')->label('发生时间')->dateTime('Y-m-d H:i'),
                TextEntry::make('occurred_date')->label('记账日期')->date('Y-m-d'),
                TextEntry::make('note')->label('备注')->placeholder('—'),
            ]);
    }
}