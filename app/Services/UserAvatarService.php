<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarService
{
    private const DISK = 'public';

    public function store(User $user, UploadedFile $file): string
    {
        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
        }

        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';

        return $file->storeAs(
            "avatars/{$user->id}",
            Str::ulid().'.'.$ext,
            self::DISK
        );
    }

    public function delete(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
        }
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }
}