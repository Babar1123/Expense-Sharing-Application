<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\CreateSettlementRequest;
use App\Models\Group;
use App\Services\GroupService;
use App\Services\SettlementService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService,
        protected GroupService $groupService,
    ) {
    }

    public function store(string $groupId, CreateSettlementRequest $request)
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);

        $settlement = $this->settlementService->create($request->user(), $groupId, $request->validated());

        return ApiResponse::success('Settlement created successfully.', $settlement);
    }
}
