<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\BookScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookScheduleController extends Controller
{
    public function show(Request $request, BookScheduleService $schedules): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        return response()->json($this->payload($book, $schedules));
    }

    public function update(Request $request, BookScheduleService $schedules): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $validated = $request->validate([
            'items' => ['required', 'array', 'max:20'],
            'items.*.label' => ['required', 'string', 'max:64'],
            'items.*.kind' => ['required', Rule::in(['income', 'expense'])],
            'items.*.day' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        $items = collect($validated['items'])
            ->map(fn (array $row) => [
                'label' => trim($row['label']),
                'kind' => $row['kind'],
                'day' => (int) $row['day'],
            ])
            ->filter(fn (array $row) => $row['label'] !== '')
            ->values()
            ->all();

        $schedules->sync($book, $items);

        return response()->json($this->payload($book->fresh(), $schedules));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Book $book, BookScheduleService $schedules): array
    {
        return [
            'items' => $schedules->itemsForBook($book),
            'summaries' => $schedules->summaries($book),
        ];
    }
}