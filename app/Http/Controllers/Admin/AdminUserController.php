<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'locked'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with(['role:id,name', 'subscriptionPlan'])
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($validated['role'] ?? null, fn ($query, $role) => $query->whereHas('role', fn ($q) => $q->where('name', $role)))
            ->when(isset($validated['status']), fn ($query) => $query->where('is_locked', $validated['status'] === 'locked'))
            ->latest()
            ->paginate($validated['limit'] ?? 10);

        $users->getCollection()->transform(fn (User $user) => $this->present($user));

        return response()->json(['status' => 'success', 'data' => $users]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->present($user->load(['role:id,name', 'subscriptionPlan'])),
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(['role' => ['required', 'string', 'exists:roles,name']]);
        $role = Role::where('name', $validated['role'])->firstOrFail();
        $user->update(['role_id' => $role->id]);

        return response()->json(['status' => 'success', 'data' => $this->present($user->fresh(['role', 'subscriptionPlan']))]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['active', 'locked'])]]);
        if ($request->user()->is($user) && $validated['status'] === 'locked') {
            return response()->json(['message' => 'Không thể tự khóa tài khoản đang đăng nhập.'], 422);
        }
        $user->update(['is_locked' => $validated['status'] === 'locked']);

        return response()->json(['status' => 'success', 'data' => $this->present($user->fresh(['role', 'subscriptionPlan']))]);
    }

    public function updateSubscription(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'plan_code' => ['nullable', 'string', 'exists:subscription_plans,plan_code'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'cancelled', 'none'])],
            'subscription_status' => ['nullable', Rule::in(['active', 'expired', 'cancelled', 'none'])],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_expires_at' => ['nullable', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = isset($validated['plan_code'])
            ? SubscriptionPlan::where('plan_code', $validated['plan_code'])->firstOrFail()
            : null;
        $status = $validated['subscription_status'] ?? $validated['status'] ?? ($plan ? 'active' : 'none');
        $startsAt = $validated['subscription_starts_at'] ?? $validated['starts_at'] ?? ($plan ? now() : null);
        $expiresAt = $validated['subscription_expires_at'] ?? $validated['expires_at'] ?? null;
        $previousPlanId = $user->subscription_plan_id;

        DB::transaction(function () use ($user, $plan, $status, $startsAt, $expiresAt, $previousPlanId, $validated) {
            $user->update([
                'subscription_plan_id' => $plan?->id,
                'subscription_status' => $status,
                'subscription_starts_at' => $startsAt,
                'subscription_expires_at' => $expiresAt,
                'auto_renew' => $validated['auto_renew'] ?? false,
                'cancelled_at' => $status === 'cancelled' ? now() : null,
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            ]);

            SubscriptionHistory::create([
                'user_id' => $user->id,
                'action' => $status === 'cancelled' ? 'cancelled' : ($previousPlanId ? 'upgraded' : 'purchased'),
                'subscription_plan_id' => $plan?->id,
                'previous_subscription_plan_id' => $previousPlanId,
                'amount' => 0,
                'billing_cycle' => $plan?->billing_cycle_type,
                'reason' => 'Admin cập nhật thủ công.',
                'subscription_starts_at' => $startsAt,
                'subscription_expires_at' => $expiresAt,
            ]);
        });

        return response()->json(['status' => 'success', 'data' => $this->present($user->fresh(['role', 'subscriptionPlan']))]);
    }

    private function present(User $user): array
    {
        return [
            ...$user->toArray(),
            'status' => $user->is_locked ? 'locked' : 'active',
            'subscription' => $user->subscription_plan_id ? [
                'plan_code' => $user->subscriptionPlan?->plan_code,
                'plan' => $user->subscriptionPlan,
                'status' => $user->subscription_status,
                'starts_at' => $user->subscription_starts_at,
                'expires_at' => $user->subscription_expires_at,
                'auto_renew' => $user->auto_renew,
            ] : null,
        ];
    }
}
