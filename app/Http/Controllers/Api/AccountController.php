<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $accounts = $book->accounts()->orderBy('sort')->get();

        return response()->json(['data' => $accounts]);
    }
}