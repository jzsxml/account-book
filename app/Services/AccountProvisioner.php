<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Book;

class AccountProvisioner
{
    /**
     * @return array<int, array{name: string, type: string, sort: int, is_default: bool}>
     */
    public static function defaultAccounts(): array
    {
        return [
            ['name' => '现金', 'type' => 'cash', 'sort' => 1, 'is_default' => true],
            ['name' => '微信', 'type' => 'ewallet', 'sort' => 2, 'is_default' => false],
            ['name' => '支付宝', 'type' => 'ewallet', 'sort' => 3, 'is_default' => false],
            ['name' => '银行卡', 'type' => 'bank', 'sort' => 4, 'is_default' => false],
        ];
    }

    public function provisionForBook(Book $book): void
    {
        if (Account::query()->where('book_id', $book->id)->exists()) {
            return;
        }

        foreach (self::defaultAccounts() as $row) {
            Account::query()->create([
                'book_id' => $book->id,
                ...$row,
            ]);
        }
    }
}