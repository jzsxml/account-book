<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\UserAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(private UserAvatarService $avatars) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $book = $user->defaultBook();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'birthday' => $user->birthday?->format('Y-m-d'),
            'avatar_url' => $this->avatars->url($user->avatar_path),
            'settings' => $user->settings,
            'default_book' => $book ? $this->bookPayload($book) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookPayload(Book $book): array
    {
        return [
            'id' => $book->id,
            'name' => $book->name,
            'currency' => $book->currency,
            'week_starts_on' => $book->week_starts_on,
            'timezone' => $book->timezone,
        ];
    }
}