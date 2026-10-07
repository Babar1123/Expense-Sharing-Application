<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Group;
use App\Models\User;

class GroupService
{
    public function createForUser(User $user, array $data): Group
    {
        $group = Group::create([
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'owner_id' => (string) $user->_id,
            'member_ids' => [(string) $user->_id],
        ]);

        return $group;
    }

    public function addMember(User $actor, string $groupId, array $data): array
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        if ($group->owner_id !== (string) $actor->_id && ! in_array((string) $actor->_id, $group->member_ids ?? [], true)) {
            throw new BusinessException('You are not allowed to manage this group.', [], 403);
        }

        $memberId = (string) $data['user_id'];
        $user = User::find($memberId);

        if (! $user) {
            throw new BusinessException('Selected user not found.', [], 404);
        }

        if (in_array($memberId, $group->member_ids ?? [], true)) {
            throw new BusinessException('The selected user is already a member of this group.', [], 422);
        }

        $group->member_ids = array_values(array_merge($group->member_ids ?? [], [$memberId]));
        $group->save();

        return [
            'user' => $user->only(['_id', 'name', 'email']),
            'group_id' => (string) $group->_id,
        ];
    }

    public function ensureGroupAccess(User $user, Group $group): void
    {
        if ($group->owner_id !== (string) $user->_id && ! in_array((string) $user->_id, $group->member_ids ?? [], true)) {
            throw new BusinessException('You do not have access to this group.', [], 403);
        }
    }
}
