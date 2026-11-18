<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\DailySummary;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookProvisioner;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', TestUserSeeder::EMAIL)->first();

        if (! $user) {
            $this->command?->warn('请先运行 TestUserSeeder：php artisan db:seed --class=TestUserSeeder');

            return;
        }

        $book = $user->defaultBook();

        if (! $book) {
            $this->command?->warn('测试用户没有账本。');

            return;
        }

        $this->ensureCategories($book);

        $incomeCategories = Category::query()
            ->where('book_id', $book->id)
            ->where('type', 'income')
            ->orderBy('sort')
            ->get();

        $expenseCategories = Category::query()
            ->where('book_id', $book->id)
            ->where('type', 'expense')
            ->orderBy('sort')
            ->get();

        if ($incomeCategories->isEmpty() || $expenseCategories->isEmpty()) {
            $this->command?->warn('分类数据不完整。');

            return;
        }

        Transaction::query()
            ->where('book_id', $book->id)
            ->orderBy('id')
            ->each(fn (Transaction $tx) => $tx->delete());

        DailySummary::query()->where('book_id', $book->id)->delete();

        $tz = $book->timezone ?? 'Asia/Shanghai';
        $today = Carbon::now($tz)->startOfDay();
        $created = 0;

        $incomePlan = [
            ['name' => '工资', 'base' => 8000, 'spread' => 500, 'every' => 7],
            ['name' => '奖金', 'base' => 1200, 'spread' => 400, 'every' => 14],
            ['name' => '兼职', 'base' => 350, 'spread' => 180, 'every' => 5],
            ['name' => '理财', 'base' => 220, 'spread' => 120, 'every' => 9],
            ['name' => '其他收入', 'base' => 80, 'spread' => 60, 'every' => 11],
        ];

        $expensePlan = [
            ['name' => '餐饮', 'base' => 45, 'spread' => 35],
            ['name' => '交通', 'base' => 18, 'spread' => 12],
            ['name' => '购物', 'base' => 120, 'spread' => 200, 'every' => 3],
            ['name' => '住房', 'base' => 80, 'spread' => 40, 'every' => 7],
            ['name' => '娱乐', 'base' => 65, 'spread' => 80, 'every' => 4],
            ['name' => '医疗', 'base' => 50, 'spread' => 90, 'every' => 8],
            ['name' => '其他支出', 'base' => 25, 'spread' => 30, 'every' => 6],
        ];

        for ($i = 34; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);

            foreach ($incomePlan as $plan) {
                $every = $plan['every'] ?? 1;
                if ($i % $every !== 0) {
                    continue;
                }
                $cat = $this->categoryByName($incomeCategories, $plan['name']);
                if (! $cat) {
                    continue;
                }
                $amount = $plan['base'] + ($i * 17 % $plan['spread']);
                $this->insertTx($book->id, $cat->id, 'income', $amount, $day, "{$plan['name']}（演示）");
                $created++;
            }

            foreach ($expensePlan as $idx => $plan) {
                $every = $plan['every'] ?? 1;
                if (($i + $idx) % $every !== 0) {
                    continue;
                }
                $cat = $this->categoryByName($expenseCategories, $plan['name']);
                if (! $cat) {
                    continue;
                }
                $amount = $plan['base'] + (($i + $idx) * 11 % max($plan['spread'], 1));
                $this->insertTx($book->id, $cat->id, 'expense', $amount, $day, "{$plan['name']}（演示）");
                $created++;
            }
        }

        for ($m = 1; $m <= 3; $m++) {
            $day = $today->copy()->subMonths($m)->day(15);
            $salary = $this->categoryByName($incomeCategories, '工资');
            if ($salary) {
                $this->insertTx($book->id, $salary->id, 'income', 6500 + $m * 100, $day, '历史工资（演示）');
                $created++;
            }
        }

        $this->command?->info("分类数：收入 {$incomeCategories->count()}，支出 {$expenseCategories->count()}");
        $this->command?->info("已为 {$user->email} 生成 {$created} 条演示流水（多分类）。");
        $this->command?->line('登录后打开「统计」切换收入/支出查看占比与折线图。');
    }

    private function ensureCategories(Book $book): void
    {
        foreach (BookProvisioner::defaultCategories() as $def) {
            Category::query()->firstOrCreate(
                [
                    'book_id' => $book->id,
                    'name' => $def['name'],
                    'type' => $def['type'],
                ],
                [
                    'icon' => $def['icon'],
                    'sort' => $def['sort'],
                ]
            );
        }
    }

    private function categoryByName(Collection $categories, string $name): ?Category
    {
        return $categories->firstWhere('name', $name);
    }

    private function insertTx(
        int $bookId,
        int $categoryId,
        string $type,
        float $amount,
        Carbon $day,
        string $note
    ): void {
        $at = $day->copy()->setTime(12, 0, 0);

        Transaction::query()->create([
            'book_id' => $bookId,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => round($amount, 2),
            'occurred_at' => $at,
            'occurred_date' => $at->toDateString(),
            'note' => $note,
        ]);
    }
}