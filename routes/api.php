<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{groupId}/members', [GroupController::class, 'members']);
    Route::post('/groups/{groupId}/members', [GroupController::class, 'addMember']);

    Route::get('/groups/{groupId}/balances', [BalanceController::class, 'index']);
    Route::get('/groups/{groupId}/debts', [BalanceController::class, 'debts']);

    Route::get('/groups/{groupId}/expenses', [ExpenseController::class, 'index']);
    Route::post('/groups/{groupId}/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{expenseId}', [ExpenseController::class, 'show']);
    Route::put('/expenses/{expenseId}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expenseId}', [ExpenseController::class, 'destroy']);

    Route::post('/groups/{groupId}/settlements', [SettlementController::class, 'store']);
});
