<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'currency',
        'week_starts_on',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'week_starts_on' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(BookBudget::class);
    }

    public function billingSchedules(): HasMany
    {
        return $this->hasMany(BookBillingSchedule::class)->orderBy('sort')->orderBy('id');
    }

    public function dailySummaries(): HasMany
    {
        return $this->hasMany(DailySummary::class);
    }
}