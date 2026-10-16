<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyDebt;
use App\Models\CompanyProfile;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    /**
     * Get company funding wallet balance and claimable funds summary.
     */
    public function funding(Request $request): JsonResponse
    {
        /** @var CompanyProfile $company */
        $company = $request->attributes->get('company');
        $user = $company->user;
        $wallet = $user->company_funding_wallet;

        $claimableTasksCount = Task::where('company_id', $company->id)
            ->where('status', 'funded')
            ->whereNull('withdrawal_request_id')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_type' => 'company_funding',
                'currency' => 'SAR',
                'total_balance' => (float) $wallet->balance,
                'available_balance' => (float) $wallet->available_balance,
                'locked_balance' => (float) $wallet->locked_balance,
                'claimable_funded_tasks_count' => $claimableTasksCount,
            ],
        ]);
    }

    /**
     * Get company debts and outstanding obligations.
     */
    public function debts(Request $request): JsonResponse
    {
        /** @var CompanyProfile $company */
        $company = $request->attributes->get('company');

        $debts = CompanyDebt::where('company_id', $company->id)
            ->latest()
            ->get();

        $totalOutstanding = (float) CompanyDebt::where('company_id', $company->id)
            ->where('status', '!=', 'paid')
            ->sum('remaining_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_outstanding_debt' => $totalOutstanding,
                'currency' => 'SAR',
                'debts' => $debts,
            ],
        ]);
    }
}
