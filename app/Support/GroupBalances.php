<?php

namespace App\Support;

use App\Models\ExpenseGroup;

class GroupBalances
{
    public static function calculate(ExpenseGroup $group): array
    {
        $members = $group->members()->orderBy('name')->get();
        $balances = $members->mapWithKeys(fn ($member) => [
            $member->id => [
                'user' => $member->only(['id', 'name', 'email', 'avatar']),
                'balance' => 0.0,
            ],
        ])->all();

        $group->loadMissing(['expenses.splits', 'settlements']);

        foreach ($group->expenses->where('approval_status', 'approved') as $expense) {
            $payerId = $expense->paid_by_user_id;

            if (isset($balances[$payerId])) {
                $balances[$payerId]['balance'] += (float) $expense->amount;
            }

            foreach ($expense->splits as $split) {
                if (isset($balances[$split->user_id])) {
                    $balances[$split->user_id]['balance'] -= (float) $split->amount_owed;
                }
            }
        }

        foreach ($group->settlements as $settlement) {
            if (isset($balances[$settlement->from_user_id])) {
                $balances[$settlement->from_user_id]['balance'] += (float) $settlement->amount;
            }

            if (isset($balances[$settlement->to_user_id])) {
                $balances[$settlement->to_user_id]['balance'] -= (float) $settlement->amount;
            }
        }

        $summary = collect($balances)->map(fn ($item) => [
            ...$item,
            'balance' => round($item['balance'], 2),
        ])->values();

        return [
            'summary' => $summary,
            'suggested_settlements' => self::suggestSettlements($summary->all()),
        ];
    }

    private static function suggestSettlements(array $balances): array
    {
        $creditors = collect($balances)
            ->filter(fn ($item) => $item['balance'] > 0.009)
            ->sortByDesc('balance')
            ->values()
            ->all();

        $debtors = collect($balances)
            ->filter(fn ($item) => $item['balance'] < -0.009)
            ->map(fn ($item) => [...$item, 'balance' => abs($item['balance'])])
            ->sortByDesc('balance')
            ->values()
            ->all();

        $suggestions = [];
        $creditorIndex = 0;
        $debtorIndex = 0;

        while ($creditorIndex < count($creditors) && $debtorIndex < count($debtors)) {
            $creditor = $creditors[$creditorIndex];
            $debtor = $debtors[$debtorIndex];
            $amount = min($creditor['balance'], $debtor['balance']);

            if ($amount > 0.009) {
                $suggestions[] = [
                    'from' => $debtor['user'],
                    'to' => $creditor['user'],
                    'amount' => round($amount, 2),
                ];
            }

            $creditors[$creditorIndex]['balance'] -= $amount;
            $debtors[$debtorIndex]['balance'] -= $amount;

            if ($creditors[$creditorIndex]['balance'] <= 0.009) {
                $creditorIndex++;
            }

            if ($debtors[$debtorIndex]['balance'] <= 0.009) {
                $debtorIndex++;
            }
        }

        return $suggestions;
    }
}
