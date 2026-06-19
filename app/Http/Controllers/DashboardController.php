<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $expenses = Expense::query()
            ->with(['category', 'group', 'payer'])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('group.members', fn ($members) => $members->where('users.id', $user->id));
            })
            ->where(fn ($query) => $query->whereNull('group_id')->orWhere('approval_status', 'approved'))
            ->latest('expense_date')
            ->latest()
            ->get();

        $monthlyExpenses = $expenses->filter(
            fn ($expense) => $expense->expense_date->toDateString() >= $startOfMonth,
        );

        return Inertia::render('Dashboard', [
            'summary' => [
                'today' => round($expenses->filter(fn ($expense) => $expense->expense_date->toDateString() === $today)->sum('amount'), 2),
                'month' => round($monthlyExpenses->sum('amount'), 2),
                'personal_month' => round($monthlyExpenses->whereNull('group_id')->sum('amount'), 2),
                'group_month' => round($monthlyExpenses->whereNotNull('group_id')->sum('amount'), 2),
                'categories_count' => $user->categories()->where('is_active', true)->count(),
                'groups_count' => $user->groups()->count(),
            ],
            'byCategory' => $monthlyExpenses
                ->groupBy(fn ($expense) => $expense->category?->name ?? 'Sin categoria')
                ->map(fn ($items, $name) => [
                    'name' => $name,
                    'amount' => round($items->sum('amount'), 2),
                    'color' => $items->first()?->category?->color ?? '#64748b',
                ])
                ->values(),
            'recentExpenses' => $expenses->take(8)->map(fn ($expense) => [
                'id' => $expense->id,
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'category' => $expense->category?->name,
                'group' => $expense->group && ! $expense->group->trashed() ? $expense->group->name : null,
                'payer' => $expense->payer?->name,
            ])->values(),
        ]);
    }
}
