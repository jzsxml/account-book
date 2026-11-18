<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Book;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait FiltersTransactions
{
    private const EXPORT_MAX_ROWS = 20000;

    /** @return array{type?: string, from?: string, to?: string, category_id?: int} */
    protected function validatedTransactionFilters(Request $request, Book $book): array
    {
        return $request->validate([
            'type' => ['sometimes', Rule::in(['income', 'expense'])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('categories', 'id')->where('book_id', $book->id),
            ],
        ]);
    }

    protected function transactionListQuery(Book $book): Builder
    {
        return Transaction::query()
            ->with(['category', 'account'])
            ->where('book_id', $book->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    /** @param  array<string, mixed>  $filters */
    protected function applyTransactionFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('occurred_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('occurred_date', '<=', $filters['to']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
    }

    protected function assertExportableRowCount(Builder $query): void
    {
        $count = (clone $query)->count();
        abort_if(
            $count > self::EXPORT_MAX_ROWS,
            422,
            sprintf('导出最多支持 %d 条记录，当前 %d 条，请缩小筛选范围。', self::EXPORT_MAX_ROWS, $count)
        );
    }
}
