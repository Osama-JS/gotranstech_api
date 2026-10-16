<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskApiController extends Controller
{
    /**
     * Submit a new logistics task to Go-Tech for investor funding.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var CompanyProfile $company */
        $company = $request->attributes->get('company');

        $validator = Validator::make($request->all(), [
            'external_task_id' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pickup_city' => 'required|string|max:100',
            'pickup_address' => 'required|string|max:255',
            'pickup_lat' => 'nullable|numeric|between:-90,90',
            'pickup_lng' => 'nullable|numeric|between:-180,180',
            'dropoff_city' => 'required|string|max:100',
            'dropoff_address' => 'required|string|max:255',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'funding_amount' => 'required|numeric|min:10|max:1000000',
            'estimated_delivery_time' => 'nullable|string|max:100',
            'duration_minutes' => 'nullable|integer|min:5|max:1440', // duration task stays available
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Check if external_task_id already exists for this company
        $existing = Task::where('company_id', $company->id)
            ->where('external_task_id', $validated['external_task_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "A task with external_task_id '{$validated['external_task_id']}' already exists.",
                'data' => [
                    'task_number' => $existing->task_number,
                    'status' => $existing->status,
                ],
            ], 409);
        }

        $durationMinutes = $validated['duration_minutes'] ?? 60; // default 60 minutes countdown
        $expiresAt = now()->addMinutes($durationMinutes);

        $companyCommissionRate = (float) $company->platform_commission_rate;
        $fundingAmount = (float) $validated['funding_amount'];
        $platformCommission = round(($fundingAmount * $companyCommissionRate) / 100, 2);

        $task = Task::create([
            'company_id' => $company->id,
            'external_task_id' => $validated['external_task_id'],
            'task_number' => Task::generateTaskNumber(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'pickup_city' => $validated['pickup_city'],
            'pickup_address' => $validated['pickup_address'],
            'pickup_lat' => $validated['pickup_lat'] ?? null,
            'pickup_lng' => $validated['pickup_lng'] ?? null,
            'dropoff_city' => $validated['dropoff_city'],
            'dropoff_address' => $validated['dropoff_address'],
            'dropoff_lat' => $validated['dropoff_lat'] ?? null,
            'dropoff_lng' => $validated['dropoff_lng'] ?? null,
            'funding_amount' => $fundingAmount,
            'company_commission_rate' => $companyCommissionRate,
            'platform_commission_amount' => $platformCommission,
            'estimated_delivery_time' => $validated['estimated_delivery_time'] ?? null,
            'expires_at' => $expiresAt,
            'status' => 'available',
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task successfully ingested and published to Go-Tech investment market.',
            'data' => [
                'task_number' => $task->task_number,
                'external_task_id' => $task->external_task_id,
                'funding_amount' => (float) $task->funding_amount,
                'currency' => 'SAR',
                'status' => $task->status,
                'expires_at' => $task->expires_at->toIso8601String(),
                'created_at' => $task->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Cancel an available task.
     */
    public function cancel(string $external_task_id, Request $request): JsonResponse
    {
        /** @var CompanyProfile $company */
        $company = $request->attributes->get('company');

        $task = Task::where('company_id', $company->id)
            ->where('external_task_id', $external_task_id)
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => "Task not found with external_task_id '{$external_task_id}'.",
            ], 404);
        }

        if ($task->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => "Cannot cancel task in status '{$task->status}'. Only 'available' tasks can be cancelled.",
            ], 400);
        }

        $task->status = 'cancelled_by_company';
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Task cancelled successfully and removed from market.',
            'data' => [
                'task_number' => $task->task_number,
                'external_task_id' => $task->external_task_id,
                'status' => $task->status,
            ],
        ]);
    }

    /**
     * Check funding and completion status of a task.
     */
    public function status(string $external_task_id, Request $request): JsonResponse
    {
        /** @var CompanyProfile $company */
        $company = $request->attributes->get('company');

        $task = Task::where('company_id', $company->id)
            ->where('external_task_id', $external_task_id)
            ->with('fundedByInvestor')
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => "Task not found with external_task_id '{$external_task_id}'.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'task_number' => $task->task_number,
                'external_task_id' => $task->external_task_id,
                'status' => $task->status,
                'funding_amount' => (float) $task->funding_amount,
                'currency' => 'SAR',
                'is_funded' => $task->status === 'funded' || $task->status === 'completed',
                'funded_at' => $task->funded_at ? $task->funded_at->toIso8601String() : null,
                'expires_at' => $task->expires_at ? $task->expires_at->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * List company submitted tasks.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var CompanyProfile $company */
        $company = $request->attributes->get('company');

        $tasks = Task::where('company_id', $company->id)
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }
}
