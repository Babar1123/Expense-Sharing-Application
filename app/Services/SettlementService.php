<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;

class SettlementService
{
    public function __construct(protected BalanceService $balanceService)
    {
    }

    public function create(User $user, string $groupId, array $data): Settlement
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        if (! in_array((string) $user->_id, $group->member_ids ?? [], true)) {
            throw new BusinessException('You are not a member of this group.', [], 403);
        }

        $paidTo = (string) $data['paid_to'];
        $amount = (float) $data['amount'];

        if ($paidTo === (string) $user->_id) {
            throw new BusinessException('Payer and receiver cannot be the same user.', [], 422);
        }

        if (! in_array($paidTo, $group->member_ids ?? [], true)) {
            throw new BusinessException('Receiver must belong to the group.', [], 422);
        }

        if ($amount <= 0) {
            throw new BusinessException('Settlement amount must be greater than zero.', [], 422);
        }

        $balances = $this->balanceService->calculateGroupBalances((string) $group->_id)['balances'];

        $currentBalance = collect($balances)->first(fn ($row) => $row['user_id'] === (string) $user->_id)['balance'] ?? 0;
        $maxSettle = max(0, -((float) $currentBalance));

        if ($amount > $maxSettle) {
            throw new BusinessException('The settlement exceeds the user outstanding debt.', [], 422);
        }

        return Settlement::create([
            'group_id' => (string) $group->_id,
            'paid_by' => (string) $user->_id,
            'paid_to' => $paidTo,
            'amount' => $amount,
            'note' => $data['note'] ?? null,
        ]);
    }
}
