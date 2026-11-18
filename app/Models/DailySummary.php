<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummary extends Model
{
    protected $fillable = [
        'book_id',
        'summary_date',
        'income_total',
        'expense_total',
    ];

    protected function casts(): array
    {
        return [
            'summary_date' => 'date',
            'income_total' => 'decimal:2',
            'expense_total' => 'decimal:2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}