<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookBillingSchedule extends Model
{
    protected $fillable = [
        'book_id',
        'label',
        'kind',
        'day',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}