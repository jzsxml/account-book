<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar_path', 'birthday', 'settings', 'is_admin', 'banned_at', 'banned_reason'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $panel->getId() === 'admin';
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null && ! $this->is_admin;
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(UserLoginLog::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function defaultBook(): ?Book
    {
        $bookId = $this->settings['default_book_id'] ?? null;

        if ($bookId) {
            return $this->books()->whereKey($bookId)->first();
        }

        return $this->books()->orderBy('id')->first();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthday' => 'date',
            'password' => 'hashed',
            'settings' => 'array',
            'is_admin' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }
}