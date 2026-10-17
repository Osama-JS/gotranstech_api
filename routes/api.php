<?php

use App\Http\Controllers\Api\V1\TaskApiController;
use App\Http\Controllers\Api\V1\WalletApiController;
use App\Http\Middleware\ValidateCompanyApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Logistics Enterprises (Go-Tech API)
|--------------------------------------------------------------------------
*/

$defineRoutes = function () {
    // Tasks Operations
    Route::get('/tasks', [TaskApiController::class, 'index']);
    Route::post('/tasks', [TaskApiController::class, 'store']);
    Route::post('/tasks/{external_task_id}/cancel', [TaskApiController::class, 'cancel']);
    Route::get('/tasks/{external_task_id}/status', [TaskApiController::class, 'status']);

    // Wallets & Settlements Inquiries
    Route::get('/wallets/funding', [WalletApiController::class, 'funding']);
    Route::get('/wallets/debts', [WalletApiController::class, 'debts']);
};

// Register for both /v1 and /api/v1 to prevent any mismatch
Route::prefix('v1')->middleware([ValidateCompanyApiKey::class])->group($defineRoutes);
Route::prefix('api/v1')->middleware([ValidateCompanyApiKey::class])->group($defineRoutes);
