<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class AccountStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $userCount = User::query()->where('is_admin', false)->count();
        $bannedCount = User::query()->where('is_admin', false)->whereNotNull('banned_at')->count();
        $todayTx = Transaction::query()->whereDate('occurred_date', $today)->count();
        $monthIncome = Transaction::query()
            ->where('type', 'income')
            ->where('occurred_date', '>=', $monthStart)
            ->sum('amount');

        return [
            Stat::make('注册用户', (string) $userCount),
            Stat::make('已封号', (string) $bannedCount)
                ->color($bannedCount > 0 ? 'danger' : 'success'),
            Stat::make('今日流水笔数', (string) $todayTx),
            Stat::make('本月全站收入', '¥'.number_format((float) $monthIncome, 2)),
        ];
    }
}