<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionAttachmentService
{
    private const DISK = 'public';

    private const MAX_FILES = 5;

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{path: string, url: string, name: string, mime: string, size: int}>
     */
    public function storeForBook(int $bookId, array $files): array
    {
        $stored = [];

        foreach (array_slice($files, 0, self::MAX_FILES) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
            $path = $file->storeAs(
                "transactions/{$bookId}",
                Str::ulid().'.'.$ext,
                self::DISK
            );

            $stored[] = [
                'path' => $path,
                'url' => Storage::disk(self::DISK)->url($path),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
            ];
        }

        return $stored;
    }

  /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function deleteAll(array $attachments): void
    {
        foreach ($attachments as $item) {
            $path = $item['path'] ?? null;
            if ($path && Storage::disk(self::DISK)->exists($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $existing
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array<string, mixed>>
     */
    public function merge(?array $existing, array $files, int $bookId): array
    {
        $list = $existing ?? [];
        $list = array_merge($list, $this->storeForBook($bookId, $files));

        return array_slice($list, 0, self::MAX_FILES);
    }

    public function purgeTransaction(Transaction $transaction): void
    {
        $attachments = $transaction->metadata['attachments'] ?? [];
        $this->deleteAll($attachments);
    }
}