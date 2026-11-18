<?php

namespace App\Filament\Resources\UserLoginLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserLoginLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('logged_in_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.name')->label('昵称')->searchable(),
                TextColumn::make('user.email')->label('邮箱')->searchable(),
                TextColumn::make('ip_address')->label('IP')->searchable(),
                TextColumn::make('user_agent')
                    ->label('设备')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('logged_in_at')
                    ->label('登录时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('logged_in_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('开始日期'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('logged_in_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('logged_in_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}