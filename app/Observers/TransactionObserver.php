<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\DailySummaryService;
use App\Services\TransactionAttachmentService;
use Carbon\Carbon;

class TransactionObserver
{
    public function __construct(private DailySummaryService $dailySummaryService) {}

    public function created(Transaction $transaction): void
    {
        $this->dailySummaryService->applyTransaction($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged(['amount', 'type', 'occurred_date', 'book_id'])) {
            return;
        }

        $original = $transaction->getOriginal();
        $before = new Transaction([
            'book_id' => (int) $original['book_id'],
            'type' => $original['type'],
            'amount' => $original['amount'],
            'occurred_date' => Carbon::parse($original['occurred_date']),
        ]);

        $this->dailySummaryService->refreshForTransactionChange($before, $transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->dailySummaryService->revertTransaction($transaction);
        app(TransactionAttachmentService::class)->purgeTransaction($transaction);
    }
}