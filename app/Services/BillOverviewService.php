<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Transaction;
use Carbon\Carbon;
class BillOverviewService
{
    public function yearly(Book $book, int $year): array
    {
        $tz = $book->timezone ?? 'Asia/Shanghai';
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz);
            $end = $start->copy()->endOfMonth();
            $from = $start->toDateString();
            $to = $end->toDateString();

            $income = $this->sumType($book->id, 'income', $from, $to);
            $expense = $this->sumType($book->id, 'expense', $from, $to);

            $months[] = [
                'year' => $year,
                'month' => $month,
                'label' => sprintf('%d-%02d', $year, $month),
                'from' => $from,
                'to' => $to,
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'balance' => round($income - $expense, 2),
            ];
        }

        $yearIncome = array_sum(array_column($months, 'income'));
        $yearExpense = array_sum(array_column($months, 'expense'));

        return [
            'year' => $year,
            'income' => round($yearIncome, 2),
            'expense' => round($yearExpense, 2),
            'balance' => round($yearIncome - $yearExpense, 2),
            'savings_rate' => $yearIncome > 0
                ? round((($yearIncome - $yearExpense) / $yearIncome) * 100, 2)
                : null,
            'months' => $months,
        ];
    }

    private function sumType(int $bookId, string $type, string $from, string $to): float
    {
        return (float) Transaction::query()
            ->where('book_id', $bookId)
            ->where('type', $type)
            ->whereBetween('occurred_date', [$from, $to])
            ->sum('amount');
    }
}