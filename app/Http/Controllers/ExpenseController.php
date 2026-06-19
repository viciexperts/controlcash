<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Expenses/Index', [
            'expenses' => Expense::query()
                ->with(['category', 'group', 'payer', 'splits.user'])
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('group.members', fn ($members) => $members->where('users.id', $user->id));
                })
                ->latest('expense_date')
                ->latest()
                ->get()
                ->map(fn ($expense) => $this->serializeExpense($expense)),
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

        DB::transaction(function () use ($request, $data) {
            $expense = Expense::create([
                'user_id' => $request->user()->id,
                'group_id' => $data['group_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'paid_by_user_id' => $data['paid_by_user_id'] ?? $request->user()->id,
                'amount' => $data['amount'],
                'description' => $data['description'],
                'expense_date' => $data['expense_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncSplits($expense, $data);
        });

        return back();
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

    private function serializeExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'notes' => $expense->notes,
            'category' => $expense->category,
            'group' => $expense->group,
            'payer' => $expense->payer,
            'splits' => $expense->splits->map(fn ($split) => [
                'id' => $split->id,
                'user' => $split->user,
                'amount_owed' => (float) $split->amount_owed,
                'is_paid' => $split->is_paid,
            ]),
        ];
    }
}
