<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\CreateExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use App\Services\GroupService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
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

        $perPage = (int) $request->query('per_page', 20);
        $expenses = Expense::query()
            ->where('group_id', $groupId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage <= 0 ? 20 : $perPage);

        return ApiResponse::success('Expenses retrieved successfully.', $expenses);
    }

    public function store(string $groupId, CreateExpenseRequest $request)
    {
        $group = Group::find($groupId);

        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);
        $expense = $this->expenseService->create($request->user(), $groupId, $request->validated());

        return ApiResponse::success('Expense created successfully.', $expense);
    }

    public function show(string $expenseId)
    {
        $expense = Expense::find($expenseId);

        if (! $expense) {
            throw new BusinessException('Expense not found.', [], 404);
        }

        $group = Group::find($expense->group_id);
        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        return ApiResponse::success('Expense retrieved successfully.', [
            'expense' => $expense,
            'group' => $group,
        ]);
    }

    public function update(string $expenseId, UpdateExpenseRequest $request)
    {
        $expense = Expense::find($expenseId);

        if (! $expense) {
            throw new BusinessException('Expense not found.', [], 404);
        }

        $group = Group::find($expense->group_id);
        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);
        $updatedExpense = $this->expenseService->update($request->user(), $expense, $request->validated());

        return ApiResponse::success('Expense updated successfully.', $updatedExpense);
    }

    public function destroy(string $expenseId, Request $request)
    {
        $expense = Expense::find($expenseId);

        if (! $expense) {
            throw new BusinessException('Expense not found.', [], 404);
        }

        $group = Group::find($expense->group_id);
        if (! $group) {
            throw new BusinessException('Group not found.', [], 404);
        }

        $this->groupService->ensureGroupAccess($request->user(), $group);
        $expense->delete();

        return ApiResponse::success('Expense deleted successfully.', []);
    }
}
