<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\AddGroupMemberRequest;
use App\Http\Requests\CreateGroupRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(protected GroupService $groupService)
    {
    }

    public function store(CreateGroupRequest $request)
    {
        $group = $this->groupService->createForUser($request->user(), $request->validated());

        return ApiResponse::success('Group created successfully.', $group);
    }

    public function addMember(string $groupId, AddGroupMemberRequest $request)
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);
        $result = $this->groupService->addMember($request->user(), $groupId, $request->validated());

        return ApiResponse::success('Group member added successfully.', $result);
    }

    public function members(string $groupId, Request $request)
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);

        $memberIds = $group->member_ids ?? [];
        $members = User::whereIn('_id', $memberIds)->get()->map(fn ($user) => $user->only(['_id', 'name', 'email']));

        return ApiResponse::success('Group members retrieved successfully.', [
            'group_id' => (string) $group->_id,
            'members' => $members,
        ]);
    }
}
