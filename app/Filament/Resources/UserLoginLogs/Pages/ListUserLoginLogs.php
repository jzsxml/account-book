<?php

namespace App\Filament\Resources\UserLoginLogs\Pages;

use App\Filament\Resources\UserLoginLogs\UserLoginLogResource;
use Filament\Resources\Pages\ListRecords;

class ListUserLoginLogs extends ListRecords
{
    protected static string $resource = UserLoginLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}