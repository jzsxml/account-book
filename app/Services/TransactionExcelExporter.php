<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExcelExporter
{
    public function download(Collection $transactions, Book $book, array $filters): StreamedResponse
    {
        $filename = $this->buildFilename($filters);

        return response()->streamDownload(function () use ($transactions, $book, $filters): void {
            $spreadsheet = $this->buildSpreadsheet($transactions, $book, $filters);
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildFilename(array $filters): string
    {
        $parts = ['记账明细'];
        if (! empty($filters['from']) && ! empty($filters['to'])) {
            $parts[] = $filters['from'].'_'.$filters['to'];
        } elseif (! empty($filters['from'])) {
            $parts[] = '自'.$filters['from'];
        } elseif (! empty($filters['to'])) {
            $parts[] = '至'.$filters['to'];
        } else {
            $parts[] = now()->format('Y-m-d');
        }

        return implode('_', $parts).'.xlsx';
    }

    private function buildSpreadsheet(Collection $transactions, Book $book, array $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('明细');

        $sheet->setCellValue('A1', '记账明细');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $rangeHint = $this->filterSummary($filters);
        $sheet->setCellValue('A2', sprintf('账本：%s%s', $book->name, $rangeHint ? " · {$rangeHint}" : ''));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF64748B');

        $headers = ['日期时间', '类型', '分类', '账户', '金额', '备注', '附件数', '记录ID'];
        $headerRow = 4;
        foreach ($headers as $index => $label) {
            $col = chr(ord('A') + $index);
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }

        $headerStyle = $sheet->getStyle("A{$headerRow}:H{$headerRow}");
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0F766E');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = $headerRow + 1;
        foreach ($transactions as $transaction) {
            $typeLabel = $transaction->type === 'income' ? '收入' : '支出';
            $attachments = $transaction->metadata['attachments'] ?? [];

            $sheet->setCellValue("A{$row}", $transaction->occurred_at->timezone($book->timezone)->format('Y-m-d H:i'));
            $sheet->setCellValue("B{$row}", $typeLabel);
            $sheet->setCellValue("C{$row}", $transaction->category?->name ?? '');
            $sheet->setCellValue("D{$row}", $transaction->account?->name ?? '');
            $sheet->setCellValue("E{$row}", (float) $transaction->amount);
            $sheet->setCellValue("F{$row}", $transaction->note ?? '');
            $sheet->setCellValue("G{$row}", count($attachments));
            $sheet->setCellValue("H{$row}", $transaction->id);

            $row++;
        }

        $lastRow = max($row - 1, $headerRow);
        $sheet->getStyle('E'.($headerRow + 1).":E{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function filterSummary(array $filters): string
    {
        $parts = [];
        if (! empty($filters['from'])) {
            $parts[] = "自 {$filters['from']}";
        }
        if (! empty($filters['to'])) {
            $parts[] = "至 {$filters['to']}";
        }
        if (! empty($filters['type'])) {
            $parts[] = $filters['type'] === 'income' ? '仅收入' : '仅支出';
        }

        return implode('，', $parts);
    }
}
