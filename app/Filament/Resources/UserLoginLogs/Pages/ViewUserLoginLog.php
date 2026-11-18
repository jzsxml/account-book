<?php

namespace App\Filament\Resources\UserLoginLogs\Pages;

use App\Filament\Resources\UserLoginLogs\UserLoginLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUserLoginLog extends ViewRecord
{
    protected static string $resource = UserLoginLogResource::class;
}