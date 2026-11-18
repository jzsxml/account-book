<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(private UserAvatarService $avatars) {}

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['remove_avatar'])) {
            $this->avatars->delete($user);
            $user->avatar_path = null;
        }

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('birthday', $validated)) {
            $user->birthday = $validated['birthday'];
        }

        $user->save();

        return response()->json($this->userPayload($user));
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => [
                'required',
                'image',
                'max:2048',
                'mimes:jpg,jpeg,png,gif,webp',
            ],
        ]);

        $user->avatar_path = $this->avatars->store($user, $validated['avatar']);
        $user->save();

        return response()->json($this->userPayload($user));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => '当前密码不正确。'], 422);
        }

        $user->password = $validated['password'];
        $user->save();

        return response()->json(['message' => '密码已更新。']);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload($user): array
    {
        $book = $user->defaultBook();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'birthday' => $user->birthday?->format('Y-m-d'),
            'avatar_url' => $this->avatars->url($user->avatar_path),
            'settings' => $user->settings,
            'default_book' => $book ? [
                'id' => $book->id,
                'name' => $book->name,
                'currency' => $book->currency,
                'week_starts_on' => $book->week_starts_on,
                'timezone' => $book->timezone,
            ] : null,
        ];
    }
}