<?php

namespace App\Filament\Resources\UserLoginLogs;

use App\Filament\Resources\UserLoginLogs\Pages\ListUserLoginLogs;
use App\Filament\Resources\UserLoginLogs\Pages\ViewUserLoginLog;
use App\Filament\Resources\UserLoginLogs\Schemas\UserLoginLogInfolist;
use App\Filament\Resources\UserLoginLogs\Tables\UserLoginLogsTable;
use App\Models\UserLoginLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserLoginLogResource extends Resource
{
    protected static ?string $model = UserLoginLog::class;

    protected static ?string $navigationLabel = '登录记录';

    protected static ?string $modelLabel = '登录记录';

    protected static ?string $pluralModelLabel = '登录记录';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->whereHas('user', fn (Builder $query) => $query->where('is_admin', false));
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserLoginLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserLoginLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserLoginLogs::route('/'),
            'view' => ViewUserLoginLog::route('/{record}'),
        ];
    }
}