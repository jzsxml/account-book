<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentBookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();

        abort_if(! $book, 404, '未找到账本');

        return response()->json([
            'id' => $book->id,
            'name' => $book->name,
            'currency' => $book->currency,
            'week_starts_on' => $book->week_starts_on,
            'timezone' => $book->timezone,
        ]);
    }
}