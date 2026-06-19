<?php

namespace App\Support;

use App\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReceiptStorage
{
    public static function disk(): string
    {
        return (string) config('filesystems.receipts_disk', config('filesystems.default', 'public'));
    }

    public static function url(Expense $expense): ?string
    {
        if (! $expense->receipt_path) {
            return null;
        }

        $storage = Storage::disk(self::disk());

        try {
            return $storage->temporaryUrl($expense->receipt_path, now()->addMinutes(15));
        } catch (Throwable) {
            return $storage->url($expense->receipt_path);
        }
    }
}
