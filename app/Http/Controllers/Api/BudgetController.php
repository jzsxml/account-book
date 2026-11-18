<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $budget = BookBudget::query()
            ->where('book_id', $book->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->first();

        return response()->json([
            'year' => (int) $validated['year'],
            'month' => (int) $validated['month'],
            'amount' => $budget ? (float) $budget->amount : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        if ($validated['amount'] === null) {
            BookBudget::query()
                ->where('book_id', $book->id)
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->delete();

            return response()->json(['year' => $validated['year'], 'month' => $validated['month'], 'amount' => null]);
        }

        $budget = BookBudget::query()->updateOrCreate(
            [
                'book_id' => $book->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            ['amount' => $validated['amount']],
        );

        return response()->json([
            'year' => $budget->year,
            'month' => $budget->month,
            'amount' => (float) $budget->amount,
        ]);
    }
}