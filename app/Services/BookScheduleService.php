<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookBillingSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookScheduleService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function itemsForBook(Book $book): array
    {
        return $book->billingSchedules()
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (BookBillingSchedule $row) => [
                'id' => $row->id,
                'label' => $row->label,
                'kind' => $row->kind,
                'day' => (int) $row->day,
                'sort' => (int) $row->sort,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaries(Book $book): array
    {
        $tz = $book->timezone ?? 'Asia/Shanghai';
        $now = now($tz);

        $rows = $book->billingSchedules()->orderBy('sort')->orderBy('id')->get();

        $summaries = $rows->map(function (BookBillingSchedule $row) use ($now) {
            $next = $this->nextOccurrence($now, (int) $row->day);
            $daysUntil = max(0, $now->copy()->startOfDay()->diffInDays($next->copy()->startOfDay()));

            return [
                'id' => $row->id,
                'label' => $row->label,
                'kind' => $row->kind,
                'day' => (int) $row->day,
                'next_date' => $next->toDateString(),
                'days_until' => $daysUntil,
                'is_today' => $daysUntil === 0,
            ];
        })->all();

        usort($summaries, fn ($a, $b) => $a['days_until'] <=> $b['days_until']);

        return $summaries;
    }

    /**
     * @param  list<array{label: string, kind: string, day: int, id?: int|null}>  $items
     */
    public function sync(Book $book, array $items): void
    {
        DB::transaction(function () use ($book, $items) {
            $book->billingSchedules()->delete();

            foreach ($items as $index => $item) {
                $book->billingSchedules()->create([
                    'label' => $item['label'],
                    'kind' => $item['kind'],
                    'day' => $item['day'],
                    'sort' => $index,
                ]);
            }
        });
    }

    public function nextOccurrence(Carbon $from, int $dayOfMonth): Carbon
    {
        $dayOfMonth = max(1, min(31, $dayOfMonth));
        $cursor = $from->copy()->startOfDay();
        $candidate = $this->dateOnMonth($cursor->copy()->startOfMonth(), $dayOfMonth);

        if ($candidate->lt($cursor)) {
            $nextMonth = $cursor->copy()->addMonthNoOverflow()->startOfMonth();

            return $this->dateOnMonth($nextMonth, $dayOfMonth);
        }

        return $candidate;
    }

    private function dateOnMonth(Carbon $monthStart, int $dayOfMonth): Carbon
    {
        $daysInMonth = $monthStart->daysInMonth;
        $day = min($dayOfMonth, $daysInMonth);

        return $monthStart->copy()->day($day)->startOfDay();
    }
}