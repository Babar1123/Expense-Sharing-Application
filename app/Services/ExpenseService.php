<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(User $user, string $groupId, array $data): Expense
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        if (! in_array((string) $user->_id, $group->member_ids ?? [], true)) {
            throw new BusinessException('You are not a member of this group.', [], 403);
        }

        $amount = (float) $data['amount'];
        $paidBy = (string) $data['paid_by'];
        $splitType = (string) $data['split_type'];

        if (! in_array($paidBy, $group->member_ids ?? [], true)) {
            throw new BusinessException('The payer must belong to the group.', [], 422);
        }

        if ($amount <= 0) {
            throw new BusinessException('Amount must be greater than zero.', [], 422);
        }

        $participants = $this->normalizeParticipants($data['participants'] ?? [], $group, $splitType, $amount);

        return DB::transaction(function () use ($group, $data, $amount, $paidBy, $splitType, $participants, $user) {
            $expense = Expense::create([
                'group_id' => (string) $group->_id,
                'description' => $data['description'],
                'amount' => $amount,
                'paid_by' => $paidBy,
                'split_type' => $splitType,
                'participants' => $participants,
                'created_by' => (string) $user->_id,
            ]);

            return $expense;
        });
    }

    public function update(User $user, Expense $expense, array $data): Expense
    {
        $group = Group::find($expense->group_id);
        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        if (! in_array((string) $user->_id, $group->member_ids ?? [], true)) {
            throw new BusinessException('You are not a member of this group.', [], 403);
        }

        $payload = $expense->toArray();
        $payload['description'] = $data['description'] ?? $expense->description;
        $payload['amount'] = isset($data['amount']) ? (float) $data['amount'] : (float) $expense->amount;
        $payload['paid_by'] = $data['paid_by'] ?? $expense->paid_by;
        $payload['split_type'] = $data['split_type'] ?? $expense->split_type;
        $payload['participants'] = isset($data['participants']) ? $data['participants'] : $expense->participants;

        $participants = $this->normalizeParticipants($payload['participants'], $group, $payload['split_type'], $payload['amount']);

        $expense->fill([
            'description' => $payload['description'],
            'amount' => $payload['amount'],
            'paid_by' => $payload['paid_by'],
            'split_type' => $payload['split_type'],
            'participants' => $participants,
        ]);

        $expense->save();

        return $expense;
    }

    protected function normalizeParticipants(array $participants, Group $group, string $splitType, float $amount): array
    {
        $memberIds = $group->member_ids ?? [];
        $normalized = [];

        foreach ($participants as $participant) {
            $userId = (string) ($participant['user_id'] ?? $participant['id'] ?? '');

            if (! in_array($userId, $memberIds, true)) {
                throw new BusinessException('All participants must belong to the group.', [], 422);
            }

            $normalized[] = [
                'user_id' => $userId,
                'amount' => (float) ($participant['amount'] ?? $participant['share'] ?? 0),
            ];
        }

        switch ($splitType) {
            case 'equal':
                if (empty($normalized)) {
                    throw new BusinessException('Equal split requires at least one participant.', [], 422);
                }

                $share = round($amount / count($normalized), 2);
                foreach ($normalized as $index => $item) {
                    $normalized[$index]['amount'] = $share;
                }
                break;

            case 'exact':
                $total = array_sum(array_column($normalized, 'amount'));
                if (abs($total - $amount) > 0.01) {
                    throw new BusinessException('Exact split amounts must equal the total expense amount.', [], 422);
                }
                break;

            case 'percentage':
                $totalPercent = array_sum(array_map(fn ($item) => (float) ($item['amount'] ?? 0), $normalized));
                if (abs($totalPercent - 100) > 0.01) {
                    throw new BusinessException('Percentage split values must total 100%.', [], 422);
                }

                foreach ($normalized as $index => $item) {
                    $normalized[$index]['amount'] = round(($amount * ((float) $item['amount'] / 100)), 2);
                }
                break;
        }

        return $normalized;
    }
}
