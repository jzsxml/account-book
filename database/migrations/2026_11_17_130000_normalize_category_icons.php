<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $map = [
        '工资' => 'wallet',
        '奖金' => 'award',
        '兼职' => 'briefcase',
        '理财' => 'trend-up',
        '其他收入' => 'plus-circle',
        '餐饮' => 'utensil',
        '交通' => 'car',
        '购物' => 'cart',
        '住房' => 'building',
        '娱乐' => 'leisure',
        '医疗' => 'health',
        '其他支出' => 'minus-circle',
    ];

    private array $allowed = [
        'wallet', 'award', 'briefcase', 'trend-up', 'plus-circle',
        'utensil', 'car', 'cart', 'building', 'leisure', 'health',
        'minus-circle', 'tag', 'folder',
    ];

    public function up(): void
    {
        Category::query()->each(function (Category $category) {
            $icon = $category->icon;

            if ($icon && in_array($icon, $this->allowed, true)) {
                return;
            }

            if (isset($this->map[$category->name])) {
                $category->icon = $this->map[$category->name];
                $category->save();

                return;
            }

            if ($icon) {
                $category->icon = 'tag';
                $category->save();
            }
        });
    }

    public function down(): void
    {
        // Irreversible: previous emoji values are not stored.
    }
};