<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookBudget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function resolveRange(Book $book, string $period, ?string $anchor = null): array
    {
        $tz = $book->timezone ?? 'Asia/Shanghai';
        $anchorDate = Carbon::parse($anchor ?? now($tz)->toDateString(), $tz);
        $weekStartsOn = (int) ($book->week_starts_on ?? 1);

        return match ($period) {
            'day' => $this->dayRange($anchorDate),
            'week' => $this->weekRange($anchorDate, $weekStartsOn),
            'year' => $this->yearRange($anchorDate),
            default => $this->monthRange($anchorDate),
        };
    }

    /**
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
    public function customRange(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $endInclusive = Carbon::parse($to)->startOfDay();

        if ($endInclusive->lt($start)) {
            throw new \InvalidArgumentException('Invalid date range');
        }

        $endExclusive = $endInclusive->copy()->addDay();

        return [
            'start' => $start,
            'end' => $endExclusive,
            'start_date' => $start->toDateString(),
            'end_date' => $endInclusive->toDateString(),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon, start_date: string, end_date: string}  $range
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
  private function previousCustomRange(array $range): array
    {
        $start = $range['start'];
        $endInclusive = Carbon::parse($range['end_date']);
        $days = $start->diffInDays($endInclusive) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        return $this->customRange($prevStart->toDateString(), $prevEnd->toDateString());
    }

    /**
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
    private function dayRange(Carbon $anchor): array
    {
        $start = $anchor->copy()->startOfDay();
        $end = $start->copy()->addDay();

        return $this->formatRange($start, $end);
    }

    /**
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
    private function weekRange(Carbon $anchor, int $weekStartsOn): array
    {
        $start = $anchor->copy()->startOfWeek($weekStartsOn);
        $end = $start->copy()->addWeek();

        return $this->formatRange($start, $end);
    }

    /**
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
    private function monthRange(Carbon $anchor): array
    {
        $start = $anchor->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        return $this->formatRange($start, $end);
    }

    /**
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
    private function yearRange(Carbon $anchor): array
    {
        $start = $anchor->copy()->startOfYear();
        $end = $start->copy()->addYear();

        return $this->formatRange($start, $end);
    }

  /**
     * @return array{start: Carbon, end: Carbon, start_date: string, end_date: string}
     */
    private function formatRange(Carbon $start, Carbon $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->copy()->subDay()->toDateString(),
        ];
    }

    public function periodOverview(Book $book, string $period, ?string $anchor = null): array
    {
        $range = $this->resolveRange($book, $period, $anchor);
        $income = (float) $this->sumForMetric($book, 'income', $range['start_date'], $range['end_date']);
        $expense = (float) $this->sumForMetric($book, 'expense', $range['start_date'], $range['end_date']);
        $days = max(1, Carbon::parse($range['start_date'])->diffInDays(Carbon::parse($range['end_date'])) + 1);

        return [
            'period' => $period,
            'range' => [
                'start' => $range['start_date'],
                'end' => $range['end_date'],
            ],
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'balance' => round($income - $expense, 2),
            'daily_expense_avg' => round($expense / $days, 2),
        ];
    }

    /**
     * @return array{
     *     streak_days: int,
     *     best_streak_days: int,
     *     logged_today: bool,
     *     week_transaction_count: int,
     *     milestones: list<array<string, mixed>>
     * }
     */
    public function bookkeepingHabits(Book $book): array
    {
        $tz = $book->timezone ?? 'Asia/Shanghai';
        $today = now($tz)->toDateString();

        $dateStrings = Transaction::query()
            ->where('book_id', $book->id)
            ->select('occurred_date')
            ->distinct()
            ->pluck('occurred_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();

        $dateSet = array_fill_keys($dateStrings, true);
        $loggedToday = isset($dateSet[$today]);

        $cursor = Carbon::parse($today, $tz);
        if (! $loggedToday) {
            $cursor->subDay();
        }

        $streak = 0;
        while (isset($dateSet[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        $range = $this->resolveRange($book, 'week');
        $weekCount = (int) $this->filteredTransactions($book, $range['start_date'], $range['end_date'])->count();
        $bestStreak = $this->longestStreakDays($dateStrings);

        return [
            'streak_days' => $streak,
            'best_streak_days' => $bestStreak,
            'logged_today' => $loggedToday,
            'week_transaction_count' => $weekCount,
            'milestones' => $this->streakMilestones($streak, $bestStreak),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function streakMilestones(int $currentStreak, int $bestStreak): array
    {
        $definitions = [
            ['id' => 'streak_7', 'label' => '坚持一周', 'target' => 7],
            ['id' => 'streak_30', 'label' => '坚持一月', 'target' => 30],
        ];

        $milestones = [];

        foreach ($definitions as $def) {
            $target = $def['target'];
            $earned = $bestStreak >= $target;
            $progressBase = $earned ? $target : min($currentStreak, $target);
            $daysToGo = $earned ? 0 : max(0, $target - $currentStreak);

            $milestones[] = [
                'id' => $def['id'],
                'label' => $def['label'],
                'target' => $target,
                'earned' => $earned,
                'days_to_go' => $daysToGo,
                'progress_percent' => $earned
                    ? 100.0
                    : round(($progressBase / $target) * 1000) / 10,
            ];
        }

        return $milestones;
    }

    /**
     * @param  list<string>  $sortedEligible
     */
    private function longestStreakDays(array $dateStrings): int
    {
        if ($dateStrings === []) {
            return 0;
        }

        sort($dateStrings);
        $best = 1;
        $current = 1;

        for ($i = 1, $count = count($dateStrings); $i < $count; $i++) {
            $prev = Carbon::parse($dateStrings[$i - 1])->startOfDay();
            $curr = Carbon::parse($dateStrings[$i])->startOfDay();

            if ($prev->copy()->addDay()->toDateString() === $curr->toDateString()) {
                $current++;
                $best = max($best, $current);
            } else {
                $current = 1;
            }
        }

        return $best;
    }

    public function monthReport(Book $book, ?string $anchor = null): array
    {
        $tz = $book->timezone ?? 'Asia/Shanghai';
        $anchorDate = Carbon::parse($anchor ?? now($tz)->toDateString(), $tz);
        $range = $this->resolveRange($book, 'month', $anchorDate->toDateString());
        $overview = $this->periodOverview($book, 'month', $anchorDate->toDateString());

        $baseQuery = $this->filteredTransactions($book, $range['start_date'], $range['end_date']);
        $incomeCount = (int) (clone $baseQuery)->where('transactions.type', 'income')->count();
        $expenseCount = (int) (clone $baseQuery)->where('transactions.type', 'expense')->count();

        $expenseTotal = (float) $overview['expense'];
        $expenseCategories = $this->byCategory($book, 'expense', $range['start_date'], $range['end_date']);
        $expenseByCategory = [];
        foreach (array_slice($expenseCategories, 0, 5) as $row) {
            $total = (float) $row['total'];
            $percent = $expenseTotal > 0 ? round(($total / $expenseTotal) * 1000) / 10 : 0.0;
            $expenseByCategory[] = [
                ...$row,
                'percent' => $percent,
            ];
        }

        $topExpenses = Transaction::query()
            ->with('category')
            ->where('book_id', $book->id)
            ->where('type', 'expense')
            ->whereBetween('occurred_date', [$range['start_date'], $range['end_date']])
            ->orderByDesc('amount')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'amount' => (float) $tx->amount,
                'occurred_at' => $tx->occurred_at?->toIso8601String(),
                'note' => $tx->note,
                'category' => $tx->category ? [
                    'id' => $tx->category->id,
                    'name' => $tx->category->name,
                ] : null,
            ])
            ->all();

        $budget = BookBudget::query()
            ->where('book_id', $book->id)
            ->where('year', $anchorDate->year)
            ->where('month', $anchorDate->month)
            ->first();

        $budgetAmount = $budget ? (float) $budget->amount : null;
        $budgetUsedPercent = null;
        if ($budgetAmount && $budgetAmount > 0) {
            $budgetUsedPercent = round(($expenseTotal / $budgetAmount) * 1000) / 10;
        }

        $income = (float) $overview['income'];
        $savingsRate = null;
        if ($income > 0) {
            $savingsRate = round((($income - $expenseTotal) / $income) * 1000) / 10;
        }

        return [
            'label' => $anchorDate->format('Y年n月'),
            'year' => $anchorDate->year,
            'month' => $anchorDate->month,
            'range' => $overview['range'],
            'income' => $overview['income'],
            'expense' => $overview['expense'],
            'balance' => $overview['balance'],
            'daily_expense_avg' => $overview['daily_expense_avg'],
            'savings_rate' => $savingsRate,
            'transaction_count' => [
                'income' => $incomeCount,
                'expense' => $expenseCount,
                'total' => $incomeCount + $expenseCount,
            ],
            'expense_by_category' => $expenseByCategory,
            'top_expenses' => $topExpenses,
            'budget' => [
                'amount' => $budgetAmount,
                'used_percent' => $budgetUsedPercent,
            ],
        ];
    }

    public function summary(
        Book $book,
        string $period,
        string $metric = 'income',
        ?string $anchor = null,
        ?string $from = null,
        ?string $to = null,
        ?int $categoryId = null,
    ): array {
        if ($from && $to) {
            $range = $this->customRange($from, $to);
            $period = 'custom';
            $prevRange = $this->previousCustomRange($range);
        } else {
            $range = $this->resolveRange($book, $period, $anchor);
            $prevRange = $this->previousRange($book, $period, $range['start']);
        }

        $total = $this->sumForMetric($book, $metric, $range['start_date'], $range['end_date'], $categoryId);
        $previousTotal = $this->sumForMetric($book, $metric, $prevRange['start_date'], $prevRange['end_date'], $categoryId);

        $changePercent = null;
        if ((float) $previousTotal != 0.0) {
            $changePercent = round((($total - $previousTotal) / $previousTotal) * 100, 2);
        }

        return [
            'period' => $period,
            'metric' => $metric,
            'range' => [
                'start' => $range['start_date'],
                'end' => $range['end_date'],
            ],
            'total' => (float) $total,
            'previous_total' => (float) $previousTotal,
            'change_percent' => $changePercent,
            'by_category' => $this->byCategory($book, $metric, $range['start_date'], $range['end_date'], $categoryId),
            'series' => $this->dailySeries($book, $metric, $range['start_date'], $range['end_date'], $categoryId),
            'category_id' => $categoryId,
        ];
    }

    private function previousRange(Book $book, string $period, Carbon $currentStart): array
    {
        $start = match ($period) {
            'day' => $currentStart->copy()->subDay(),
            'week' => $currentStart->copy()->subWeek(),
            'year' => $currentStart->copy()->subYear(),
            default => $currentStart->copy()->subMonth(),
        };

        $range = match ($period) {
            'day' => $this->dayRange($start),
            'week' => $this->weekRange($start, (int) ($book->week_starts_on ?? 1)),
            'year' => $this->yearRange($start),
            default => $this->monthRange($start),
        };

        return $range;
    }

    private function sumForMetric(Book $book, string $metric, string $startDate, string $endDate, ?int $categoryId = null): string
    {
        $query = $this->filteredTransactions($book, $startDate, $endDate, $categoryId);

        return match ($metric) {
            'expense' => (string) $query->where('transactions.type', 'expense')->sum('transactions.amount'),
            'net' => (string) (
                (clone $query)->where('transactions.type', 'income')->sum('transactions.amount')
                - (clone $query)->where('transactions.type', 'expense')->sum('transactions.amount')
            ),
            default => (string) $query->where('transactions.type', 'income')->sum('transactions.amount'),
        };
    }

    /**
     * @return list<array{category_id: int, name: string, total: float}>
     */
    private function byCategory(Book $book, string $metric, string $startDate, string $endDate, ?int $categoryId = null): array
    {
        $type = $metric === 'expense' ? 'expense' : ($metric === 'income' ? 'income' : null);

        $query = $this->filteredTransactions($book, $startDate, $endDate, $categoryId)
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->select(
                'categories.id as category_id',
                'categories.name',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total');

        if ($type) {
            $query->where('transactions.type', $type);
        }

        return $query->get()->map(fn ($row) => [
            'category_id' => (int) $row->category_id,
            'name' => $row->name,
            'total' => (float) $row->total,
        ])->all();
    }

    /**
     * @return list<array{date: string, total: float}>
     */
    private function filteredTransactions(Book $book, string $startDate, string $endDate, ?int $categoryId = null)
    {
        $query = Transaction::query()
            ->where('transactions.book_id', $book->id)
            ->whereBetween('transactions.occurred_date', [$startDate, $endDate]);

        if ($categoryId) {
            $query->where('transactions.category_id', $categoryId);
        }

        return $query;
    }

    private function dailySeries(Book $book, string $metric, string $startDate, string $endDate, ?int $categoryId = null): array
    {
        $query = $this->filteredTransactions($book, $startDate, $endDate, $categoryId)
            ->select('transactions.occurred_date', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('transactions.occurred_date')
            ->orderBy('transactions.occurred_date');

        if ($metric === 'income') {
            $query->where('transactions.type', 'income');
        } elseif ($metric === 'expense') {
            $query->where('transactions.type', 'expense');
        } else {
            $query->select(
                'transactions.occurred_date',
                DB::raw("SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE -transactions.amount END) as total")
            );
        }

        return $query->get()->map(fn ($row) => [
            'date' => $row->occurred_date instanceof \Carbon\Carbon
                ? $row->occurred_date->toDateString()
                : (string) $row->occurred_date,
            'total' => (float) $row->total,
        ])->all();
    }
}