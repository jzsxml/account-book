<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FiltersTransactions;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionAttachmentService;
use App\Services\TransactionExcelExporter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    use FiltersTransactions;

    public function __construct(
        private TransactionAttachmentService $attachments,
        private TransactionExcelExporter $excelExporter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $filters = $this->validatedTransactionFilters($request, $book);
        $query = $this->transactionListQuery($book);
        $this->applyTransactionFilters($query, $filters);

        return response()->json($query->cursorPaginate(20));
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $filters = $this->validatedTransactionFilters($request, $book);

        if (! empty($filters['from']) && ! empty($filters['to']) && $filters['from'] > $filters['to']) {
            return response()->json(['message' => '开始日期不能晚于结束日期。'], 422);
        }

        $query = $this->transactionListQuery($book);
        $this->applyTransactionFilters($query, $filters);
        $this->assertExportableRowCount($query);

        $transactions = $query->get();

        return $this->excelExporter->download($transactions, $book, $filters);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $book = request()->user()->defaultBook();
        abort_if(! $book || $transaction->book_id !== $book->id, 403);

        return response()->json($transaction->load(['category', 'account']));
    }

    public function store(Request $request): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book, 404, '未找到账本');

        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('book_id', $book->id),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'account_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('book_id', $book->id),
            ],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf',
            ],
        ]);

        $accountId = $validated['account_id'] ?? null;
        if (! $accountId) {
            $accountId = $book->accounts()->where('is_default', true)->value('id')
                ?? $book->accounts()->orderBy('sort')->value('id');
        }

        $category = Category::query()->findOrFail($validated['category_id']);
        if ($category->type !== $validated['type']) {
            return response()->json(['message' => '分类类型与记账类型不一致。'], 422);
        }

        $occurredAt = isset($validated['occurred_at'])
            ? Carbon::parse($validated['occurred_at'], $book->timezone)
            : now($book->timezone);

        $metadata = null;
        if ($request->hasFile('attachments')) {
            $metadata = [
                'attachments' => $this->attachments->storeForBook(
                    $book->id,
                    $request->file('attachments', [])
                ),
            ];
        }

        $transaction = Transaction::query()->create([
            'book_id' => $book->id,
            'account_id' => $accountId,
            'category_id' => $validated['category_id'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'occurred_at' => $occurredAt,
            'occurred_date' => $occurredAt->toDateString(),
            'note' => $validated['note'] ?? null,
            'metadata' => $metadata,
        ]);

        $typeLabel = $validated['type'] === 'income' ? '收入' : '支出';

        return response()->json([
            ...$transaction->load(['category', 'account'])->toArray(),
            'flash_message' => sprintf('已记录%s ¥%s', $typeLabel, $validated['amount']),
        ], 201);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $book = $request->user()->defaultBook();
        abort_if(! $book || $transaction->book_id !== $book->id, 403);

        $validated = $request->validate([
            'category_id' => [
                'sometimes',
                Rule::exists('categories', 'id')->where('book_id', $book->id),
            ],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'decimal:0,2'],
            'type' => ['sometimes', Rule::in(['income', 'expense'])],
            'occurred_at' => ['sometimes', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'account_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('book_id', $book->id),
            ],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf',
            ],
            'remove_attachment_paths' => ['sometimes', 'array'],
            'remove_attachment_paths.*' => ['string'],
        ]);

        if (isset($validated['category_id'])) {
            $category = Category::query()->findOrFail($validated['category_id']);
            $type = $validated['type'] ?? $transaction->type;
            if ($category->type !== $type) {
                return response()->json(['message' => '分类类型与记账类型不一致。'], 422);
            }
        }

        if (isset($validated['occurred_at'])) {
            $occurredAt = Carbon::parse($validated['occurred_at'], $book->timezone);
            $validated['occurred_at'] = $occurredAt;
            $validated['occurred_date'] = $occurredAt->toDateString();
        }

        $metadata = $transaction->metadata ?? [];
        $current = $metadata['attachments'] ?? [];

        if (! empty($validated['remove_attachment_paths'] ?? [])) {
            $toRemove = $validated['remove_attachment_paths'];
            $kept = [];
            $deleted = [];
            foreach ($current as $item) {
                if (in_array($item['path'] ?? '', $toRemove, true)) {
                    $deleted[] = $item;
                } else {
                    $kept[] = $item;
                }
            }
            $this->attachments->deleteAll($deleted);
            $current = $kept;
        }

        if ($request->hasFile('attachments')) {
            $current = $this->attachments->merge(
                $current,
                $request->file('attachments', []),
                $book->id
            );
        }

        $metadata['attachments'] = $current;
        $validated['metadata'] = $metadata;

        unset($validated['attachments'], $validated['remove_attachment_paths']);

        $transaction->update($validated);

        return response()->json($transaction->fresh('category'));
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $book = request()->user()->defaultBook();
        abort_if(! $book || $transaction->book_id !== $book->id, 403);

        $transaction->delete();

        return response()->json(['message' => '已删除']);
    }
}
