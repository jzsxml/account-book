<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->copyable(),
                IconColumn::make('email_verified_at')
                    ->label('已验证')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->hasVerifiedEmail()),
                TextColumn::make('books_count')
                    ->counts('books')
                    ->label('账本数'),
                TextColumn::make('banned_at')
                    ->label('封号时间')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('正常')
                    ->sortable(),
                TextColumn::make('banned_reason')
                    ->label('封号原因')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('banned')
                    ->label('已封号')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('banned_at')),
                Filter::make('active')
                    ->label('正常用户')
                    ->query(fn (Builder $query): Builder => $query->whereNull('banned_at')),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                Action::make('ban')
                    ->label('封号')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->visible(fn (User $record): bool => ! $record->is_admin && $record->banned_at === null)
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('banned_reason')
                            ->label('原因（可选）')
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'banned_at' => now(),
                            'banned_reason' => $data['banned_reason'] ?? null,
                        ]);
                    }),
                Action::make('unban')
                    ->label('解封')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (User $record): bool => ! $record->is_admin && $record->banned_at !== null)
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'banned_at' => null,
                            'banned_reason' => null,
                        ]);
                    }),
            ]);
    }
}