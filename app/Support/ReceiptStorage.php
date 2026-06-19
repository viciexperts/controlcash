<?php

namespace App\Support;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReceiptStorage
{
    public static function disk(): string
    {
        return (string) config('filesystems.receipts_disk', config('filesystems.default', 'public'));
    }

    public static function store(UploadedFile $file): string
    {
        try {
            $path = Storage::disk(self::disk())->putFile('receipts', $file);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'receipt' => __('No se pudo subir el recibo. Revisa la configuracion de almacenamiento.'),
            ]);
        }

        if (! $path) {
            throw ValidationException::withMessages([
                'receipt' => __('No se pudo subir el recibo. Revisa la configuracion de almacenamiento.'),
            ]);
        }

        return $path;
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
