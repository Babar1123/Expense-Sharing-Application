<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;

class BalanceService
{
    public function calculateGroupBalances(string $groupId): array
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $balances = [];

        foreach ($group->member_ids ?? [] as $memberId) {
            $balances[(string) $memberId] = 0.0;
        }

        $expenses = Expense::query()->where('group_id', $groupId)->get();

        foreach ($expenses as $expense) {
            foreach ($expense->participants ?? [] as $participant) {
                $userId = (string) ($participant['user_id'] ?? '');
                if (isset($balances[$userId])) {
                    $balances[$userId] -= (float) ($participant['amount'] ?? 0);
                }
            }

            $payerId = (string) $expense->paid_by;
            if (isset($balances[$payerId])) {
                $balances[$payerId] += (float) ($expense->amount ?? 0);
            }
        }

        $settlements = Settlement::query()->where('group_id', $groupId)->get();

        foreach ($settlements as $settlement) {
            $paidBy = (string) $settlement->paid_by;
            $paidTo = (string) $settlement->paid_to;

            if (isset($balances[$paidBy])) {
                $balances[$paidBy] += (float) $settlement->amount;
            }

            if (isset($balances[$paidTo])) {
                $balances[$paidTo] -= (float) $settlement->amount;
            }
        }

        $result = [];

        foreach ($group->member_ids ?? [] as $memberId) {
            $user = User::find($memberId);
            $result[] = [
                'user_id' => (string) $memberId,
                'name' => $user?->name ?? 'Unknown User',
                'balance' => round((float) ($balances[(string) $memberId] ?? 0), 2),
            ];
        }

        return [
            'group_id' => (string) $groupId,
            'balances' => $result,
        ];
    }

    public function simplifyDebts(string $groupId): array
    {
        $balances = $this->calculateGroupBalances($groupId)['balances'];
        $creditors = [];
        $debtors = [];

        foreach ($balances as $entry) {
            if ($entry['balance'] > 0) {
                $creditors[] = $entry;
            }

            if ($entry['balance'] < 0) {
                $debtors[] = [
                    'user_id' => $entry['user_id'],
                    'name' => $entry['name'],
                    'balance' => abs((float) $entry['balance']),
                ];
            }
        }

        $transactions = [];

        foreach ($debtors as $debtor) {
            $remaining = $debtor['balance'];

            foreach ($creditors as $creditor) {
                if ($remaining <= 0) {
                    break;
                }

                $amount = min($remaining, (float) $creditor['balance']);

                if ($amount <= 0) {
                    continue;
                }

                $transactions[] = [
                    'from' => $debtor['user_id'],
                    'to' => $creditor['user_id'],
                    'amount' => round($amount, 2),
                ];

                $remaining -= $amount;
                $creditor['balance'] = (float) $creditor['balance'] - $amount;
            }
        }

        return $transactions;
    }
}
