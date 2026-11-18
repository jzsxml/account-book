<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $query = Category::query()->where('book_id', $book->id)->orderBy('sort');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:32',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $query->where('book_id', $book->id)->where('type', $request->input('type'))),
            ],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'icon' => [
                'nullable',
                'string',
                'max:32',
                Rule::in([
                    'wallet', 'award', 'briefcase', 'trend-up', 'plus-circle',
                    'utensil', 'car', 'cart', 'building', 'leisure', 'health',
                    'minus-circle', 'tag', 'folder',
                ]),
            ],
        ]);

        $sort = (int) Category::query()
            ->where('book_id', $book->id)
            ->where('type', $validated['type'])
            ->max('sort');

        $category = Category::query()->create([
            'book_id' => $book->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?: 'tag',
            'sort' => $sort + 1,
        ]);

        return response()->json($category, 201);
    }
}