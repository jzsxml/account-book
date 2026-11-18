<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function overview(Request $request, BillOverviewService $bills): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = (int) ($validated['year'] ?? now($book->timezone)->year);

        return response()->json($bills->yearly($book, $year));
    }
}