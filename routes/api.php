<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BookScheduleController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CurrentBookController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'not_banned'])->group(function () {
    Route::get('/me', MeController::class);
    Route::patch('/me', [ProfileController::class, 'update']);
    Route::post('/me/avatar', [ProfileController::class, 'updateAvatar']);
    Route::put('/me/password', [ProfileController::class, 'updatePassword']);
    Route::get('/current-book', CurrentBookController::class);
    Route::get('/book/schedules', [BookScheduleController::class, 'show']);
    Route::put('/book/schedules', [BookScheduleController::class, 'update']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/stats', StatsController::class);
    Route::get('/bills/overview', [BillController::class, 'overview']);
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/budget', [BudgetController::class, 'show']);
    Route::put('/budget', [BudgetController::class, 'update']);
    Route::get('/transactions/export', [TransactionController::class, 'export']);
    Route::apiResource('transactions', TransactionController::class);
});