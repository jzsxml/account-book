<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;

class BookProvisioner
{
    /**
     * @return array<int, array{name: string, type: string, icon: string, sort: int}>
     */
    public static function defaultCategories(): array
    {
        return [
            ['name' => '工资', 'type' => 'income', 'icon' => 'wallet', 'sort' => 1],
            ['name' => '奖金', 'type' => 'income', 'icon' => 'award', 'sort' => 2],
            ['name' => '兼职', 'type' => 'income', 'icon' => 'briefcase', 'sort' => 3],
            ['name' => '理财', 'type' => 'income', 'icon' => 'trend-up', 'sort' => 4],
            ['name' => '其他收入', 'type' => 'income', 'icon' => 'plus-circle', 'sort' => 5],
            ['name' => '餐饮', 'type' => 'expense', 'icon' => 'utensil', 'sort' => 10],
            ['name' => '交通', 'type' => 'expense', 'icon' => 'car', 'sort' => 11],
            ['name' => '购物', 'type' => 'expense', 'icon' => 'cart', 'sort' => 12],
            ['name' => '住房', 'type' => 'expense', 'icon' => 'building', 'sort' => 13],
            ['name' => '娱乐', 'type' => 'expense', 'icon' => 'leisure', 'sort' => 14],
            ['name' => '医疗', 'type' => 'expense', 'icon' => 'health', 'sort' => 15],
            ['name' => '其他支出', 'type' => 'expense', 'icon' => 'minus-circle', 'sort' => 16],
        ];
    }

    public function provisionForUser(User $user): Book
    {
        $book = Book::query()->create([
            'user_id' => $user->id,
            'name' => '我的账本',
            'currency' => 'CNY',
            'week_starts_on' => 1,
            'timezone' => 'Asia/Shanghai',
        ]);

        foreach (self::defaultCategories() as $category) {
            Category::query()->create([
                'book_id' => $book->id,
                ...$category,
            ]);
        }

        app(AccountProvisioner::class)->provisionForBook($book);

        $settings = $user->settings ?? [];
        $settings['default_book_id'] = $book->id;
        $settings['timezone'] = $settings['timezone'] ?? 'Asia/Shanghai';
        $user->settings = $settings;
        $user->save();

        return $book;
    }
}