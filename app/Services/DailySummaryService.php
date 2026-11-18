<?php

namespace App\Services;

use App\Models\DailySummary;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DailySummaryService
{
    public function applyTransaction(Transaction $transaction, int $direction = 1): void
    {
        $date = $transaction->occurred_date->toDateString();
        $column = $transaction->type === 'income' ? 'income_total' : 'expense_total';
        $delta = bcmul((string) $transaction->amount, (string) $direction, 2);

        DB::transaction(function () use ($transaction, $date, $column, $delta) {
            $summary = DailySummary::query()
                ->where('book_id', $transaction->book_id)
                ->where('summary_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $summary) {
                DailySummary::query()->create([
                    'book_id' => $transaction->book_id,
                    'summary_date' => $date,
                    'income_total' => $column === 'income_total' ? $delta : 0,
                    'expense_total' => $column === 'expense_total' ? $delta : 0,
                ]);

                return;
            }

            $summary->{$column} = bcadd((string) $summary->{$column}, $delta, 2);
            $summary->save();
        });
    }

    public function revertTransaction(Transaction $transaction): void
    {
        $this->applyTransaction($transaction, -1);
    }

    public function refreshForTransactionChange(
        Transaction $before,
        Transaction $after
    ): void {
        if ($before->occurred_date->toDateString() !== $after->occurred_date->toDateString()
            || $before->type !== $after->type
            || (string) $before->amount !== (string) $after->amount) {
            $this->revertTransaction($before);
            $this->applyTransaction($after);
        }
    }
}