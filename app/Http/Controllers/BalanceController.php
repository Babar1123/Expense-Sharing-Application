<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Models\Group;
use App\Services\BalanceService;
use App\Services\GroupService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService,
        protected GroupService $groupService,
    ) {
    }

    public function index(string $groupId, Request $request)
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);

        return ApiResponse::success('Group balances retrieved successfully.', $this->balanceService->calculateGroupBalances($groupId));
    }

    public function debts(string $groupId, Request $request)
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);

        return ApiResponse::success('Debt simplification retrieved successfully.', [
            'group_id' => $groupId,
            'debts' => $this->balanceService->simplifyDebts($groupId),
        ]);
    }
}
