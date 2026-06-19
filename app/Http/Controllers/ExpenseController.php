<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseComment;
use App\Models\ExpenseGroup;
use App\Notifications\GroupExpenseCreated;
use App\Support\ReceiptStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Expenses/Index', [
            'expenses' => Expense::query()
                ->with(['category', 'group', 'payer', 'approver', 'splits.user', 'comments.user'])
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('group.members', fn ($members) => $members->where('users.id', $user->id));
                })
                ->latest('expense_date')
                ->latest()
                ->get()
                ->map(fn ($expense) => $this->serializeExpense($expense, $user)),
            'categories' => Category::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->where('user_id', $user->id)->orWhereNull('user_id'))
                ->orderBy('name')
                ->get(),
            'groups' => $user->groups()->with('members:id,name,email')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $receipt = $this->storeReceipt($request);

        $expense = DB::transaction(function () use ($request, $data, $receipt) {
            $expense = $this->createExpense($request, $data, $receipt);
            $this->syncSplits($expense, $data);

            if (! empty($data['is_recurring'])) {
                $this->createRecurringExpenses($request, $expense, $data);
            }

            return $expense;
        });

        $this->notifyGroupMembers($expense, $request);

        return back();
    }

    public function comment(Request $request, Expense $expense)
    {
        $this->authorizeExpense($request, $expense);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        ExpenseComment::create([
            'expense_id' => $expense->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back();
    }

    public function approve(Request $request, Expense $expense)
    {
        $this->authorizeGroupAdmin($request, $expense);

        $expense->update([
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back();
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $expenses = $this->visibleExpenses($request)
            ->with(['category', 'group', 'payer', 'approver'])
            ->latest('expense_date')
            ->latest()
            ->get();

        return $format === 'csv'
            ? $this->exportCsv($expenses)
            : $this->exportPdf($expenses);
    }

    private function createExpense(Request $request, array $data, array $receipt = []): Expense
    {
        return Expense::create([
            'user_id' => $request->user()->id,
            'group_id' => $data['group_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'paid_by_user_id' => $data['paid_by_user_id'] ?? $request->user()->id,
            'amount' => $data['amount'],
            'description' => $data['description'],
            'expense_date' => $data['expense_date'],
            'notes' => $data['notes'] ?? null,
            'receipt_path' => $receipt['path'] ?? null,
            'receipt_original_name' => $receipt['name'] ?? null,
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'recurring_day' => ! empty($data['is_recurring'])
                ? Carbon::parse($data['expense_date'])->day
                : null,
            'approval_status' => empty($data['group_id']) ? 'approved' : 'pending',
            'approved_by' => empty($data['group_id']) ? $request->user()->id : null,
            'approved_at' => empty($data['group_id']) ? now() : null,
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeExpense($request, $expense);
        $data = $this->validatedData($request);

        DB::transaction(function () use ($expense, $data, $request) {
            $expense->update([
                'group_id' => $data['group_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'paid_by_user_id' => $data['paid_by_user_id'] ?? $request->user()->id,
                'amount' => $data['amount'],
                'description' => $data['description'],
                'expense_date' => $data['expense_date'],
                'notes' => $data['notes'] ?? null,
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recurring_day' => ! empty($data['is_recurring'])
                    ? Carbon::parse($data['expense_date'])->day
                    : null,
                'approval_status' => empty($data['group_id']) ? 'approved' : $expense->approval_status,
            ]);

            $expense->splits()->delete();
            $this->syncSplits($expense, $data);
        });

        return back();
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeExpense($request, $expense);
        $expense->delete();

        return back();
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'paid_by_user_id' => ['nullable', 'exists:users,id'],
            'split_type' => ['required', 'in:equal,exact'],
            'participant_ids' => ['array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
            'splits' => ['array'],
            'splits.*.user_id' => ['required_with:splits', 'integer', 'exists:users,id'],
            'splits.*.amount' => ['required_with:splits', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:4096'],
            'is_recurring' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['group_id'])) {
            $group = ExpenseGroup::findOrFail($data['group_id']);
            abort_unless($group->members()->where('users.id', $request->user()->id)->exists(), 403);

            $memberIds = $group->members()->pluck('users.id')->all();

            if (empty($data['paid_by_user_id']) || ! in_array((int) $data['paid_by_user_id'], $memberIds, true)) {
                throw ValidationException::withMessages([
                    'paid_by_user_id' => __('Selecciona un miembro del grupo como pagador.'),
                ]);
            }

            foreach ($data['participant_ids'] ?? [] as $participantId) {
                if (! in_array((int) $participantId, $memberIds, true)) {
                    throw ValidationException::withMessages([
                        'participant_ids' => __('Los participantes deben pertenecer al grupo seleccionado.'),
                    ]);
                }
            }
        }

        return $data;
    }

    private function createRecurringExpenses(Request $request, Expense $expense, array $data): void
    {
        $date = Carbon::parse($data['expense_date']);

        for ($month = 1; $month <= 11; $month++) {
            $nextDate = $date->copy()->addMonthsNoOverflow($month);
            $recurringExpense = Expense::create([
                ...$expense->only([
                    'user_id',
                    'group_id',
                    'category_id',
                    'paid_by_user_id',
                    'amount',
                    'description',
                    'notes',
                    'receipt_path',
                    'receipt_original_name',
                    'is_recurring',
                    'recurring_day',
                    'approval_status',
                    'approved_by',
                    'approved_at',
                ]),
                'expense_date' => $nextDate->toDateString(),
                'recurrence_parent_id' => $expense->id,
            ]);

            $this->syncSplits($recurringExpense, $data);
        }
    }

    private function storeReceipt(Request $request): array
    {
        if (! $request->hasFile('receipt')) {
            return [];
        }

        $file = $request->file('receipt');

        return [
            'path' => ReceiptStorage::store($file),
            'name' => $file->getClientOriginalName(),
        ];
    }

    private function notifyGroupMembers(Expense $expense, Request $request): void
    {
        if (empty($expense->group_id)) {
            return;
        }

        $expense->loadMissing(['group.members', 'owner', 'payer', 'category']);

        $expense->group
            ->members
            ->reject(fn ($member) => $member->id === $request->user()->id)
            ->each(function ($member) use ($expense) {
                try {
                    $member->notify(new GroupExpenseCreated($expense));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }

    private function syncSplits(Expense $expense, array $data): void
    {
        if (empty($data['group_id'])) {
            return;
        }

        if ($data['split_type'] === 'exact') {
            $splits = collect($data['splits'] ?? [])
                ->filter(fn ($split) => (float) $split['amount'] > 0)
                ->values();
        } else {
            $participantIds = collect($data['participant_ids'] ?? [])
                ->unique()
                ->values();

            if ($participantIds->isEmpty()) {
                $participantIds = $expense->group->members()->pluck('users.id');
            }

            $share = round(((float) $expense->amount) / max($participantIds->count(), 1), 2);
            $splits = $participantIds->map(fn ($userId) => [
                'user_id' => $userId,
                'amount' => $share,
            ]);
        }

        $total = round($splits->sum(fn ($split) => (float) $split['amount']), 2);
        abort_unless(abs($total - (float) $expense->amount) <= 0.05, 422);

        $splits->each(fn ($split) => $expense->splits()->create([
            'user_id' => $split['user_id'],
            'amount_owed' => $split['amount'],
            'share_type' => $data['split_type'],
        ]));
    }

    private function authorizeExpense(Request $request, Expense $expense): void
    {
        $user = $request->user();

        abort_unless(
            $expense->user_id === $user->id
            || ($expense->group && $expense->group->members()->where('users.id', $user->id)->exists()),
            403,
        );
    }

    private function authorizeGroupAdmin(Request $request, Expense $expense): void
    {
        abort_unless($expense->group, 404);

        abort_unless(
            $expense->group
                ->members()
                ->where('users.id', $request->user()->id)
                ->wherePivot('role', 'admin')
                ->exists(),
            403,
        );
    }

    private function visibleExpenses(Request $request)
    {
        $user = $request->user();

        return Expense::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('group.members', fn ($members) => $members->where('users.id', $user->id));
            });
    }

    private function exportCsv($expenses): StreamedResponse
    {
        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Descripcion', 'Categoria', 'Grupo', 'Pagado por', 'Monto', 'Estado']);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->expense_date->format('Y-m-d'),
                    $expense->description,
                    $expense->category?->name ?? 'Sin categoria',
                    $expense->group?->name ?? 'Personal',
                    $expense->payer?->name ?? '',
                    (float) $expense->amount,
                    $expense->approval_status,
                ]);
            }

            fclose($handle);
        }, 'gastos-controlcash.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportPdf($expenses)
    {
        $rows = $expenses->map(fn ($expense) => [
            $expense->expense_date->format('Y-m-d'),
            Str::limit($expense->description, 34, ''),
            Str::limit($expense->category?->name ?? 'Sin categoria', 18, ''),
            number_format((float) $expense->amount, 2),
            $expense->approval_status,
        ])->all();

        $pdf = $this->buildSimplePdf([
            'ControlCash - Gastos',
            'Generado por '.Auth::user()->name.' el '.now()->format('Y-m-d H:i'),
            '',
            'Fecha       Descripcion                        Categoria          Monto        Estado',
            '--------------------------------------------------------------------------------',
            ...array_map(fn ($row) => sprintf('%-11s %-34s %-18s %10s  %s', ...$row), $rows),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="gastos-controlcash.pdf"',
        ]);
    }

    private function buildSimplePdf(array $lines): string
    {
        $content = "BT\n/F1 10 Tf\n50 780 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -14 Td\n";
            }

            $content .= '('.$this->escapePdfText($line).") Tj\n";
        }

        $content .= 'ET';

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($content)." >>\nstream\n$content\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function serializeExpense(Expense $expense, $user = null): array
    {
        $visibleGroup = $expense->group && ! $expense->group->trashed()
            ? $expense->group
            : null;
        $canApprove = $user && $expense->group
            ? $expense->group
                ->members()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'admin')
                ->exists()
            : false;

        return [
            'id' => $expense->id,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'notes' => $expense->notes,
            'receipt_url' => ReceiptStorage::url($expense),
            'receipt_original_name' => $expense->receipt_original_name,
            'is_recurring' => $expense->is_recurring,
            'recurring_day' => $expense->recurring_day,
            'approval_status' => $expense->approval_status,
            'can_approve' => $canApprove,
            'approver' => $expense->approver,
            'category' => $expense->category,
            'group' => $visibleGroup,
            'payer' => $expense->payer,
            'splits' => $expense->splits->map(fn ($split) => [
                'id' => $split->id,
                'user' => $split->user,
                'amount_owed' => (float) $split->amount_owed,
                'is_paid' => $split->is_paid,
            ]),
            'comments' => $expense->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at->format('Y-m-d H:i'),
                'user' => $comment->user?->only(['id', 'name', 'email']),
            ]),
        ];
    }
}
