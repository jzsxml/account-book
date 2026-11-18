<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatsController extends Controller
{
    public function __invoke(Request $request, StatsService $statsService): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        if ($request->boolean('habits')) {
            return response()->json($statsService->bookkeepingHabits($book));
        }

        if ($request->boolean('month_report')) {
            $validated = $request->validate([
                'anchor' => ['nullable', 'date'],
            ]);

            return response()->json(
                $statsService->monthReport($book, $validated['anchor'] ?? null),
            );
        }

        if ($request->boolean('overview')) {
            $validated = $request->validate([
                'period' => ['sometimes', Rule::in(['day', 'week', 'month', 'year'])],
                'anchor' => ['nullable', 'date'],
            ]);

            return response()->json(
                $statsService->periodOverview(
                    $book,
                    $validated['period'] ?? 'month',
                    $validated['anchor'] ?? null,
                ),
            );
        }

        $validated = $request->validate([
            'period' => ['sometimes', Rule::in(['week', 'month', 'year'])],
            'metric' => ['sometimes', Rule::in(['income', 'expense', 'net'])],
            'anchor' => ['nullable', 'date'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('categories', 'id')->where('book_id', $book->id),
            ],
        ]);

        if (isset($validated['from']) xor isset($validated['to'])) {
            return response()->json(['message' => '请同时填写开始与结束日期。'], 422);
        }

        if (
            isset($validated['from'], $validated['to'])
            && $validated['from'] > $validated['to']
        ) {
            return response()->json(['message' => '开始日期不能晚于结束日期。'], 422);
        }

        $summary = $statsService->summary(
            $book,
            $validated['period'] ?? 'month',
            $validated['metric'] ?? 'income',
            $validated['anchor'] ?? null,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            isset($validated['category_id']) ? (int) $validated['category_id'] : null,
        );

        return response()->json($summary);
    }
}