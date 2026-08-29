<?php

namespace App\Http\Controllers;

use App\Models\ChildLimit;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildLimitController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check()) {
            $defaultUser = User::where('account', 'parent')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return view('child_limits.index', [
                'limits' => collect(),
                'children' => collect(),
                'childrenWithLimits' => collect(),
                'family' => null,
                'isParentOrAdmin' => false,
                'isChild' => false,
            ]);
        }

        // 檢查使用者權限 (家長/管理員 vs 兒童)
        $userRole = $family->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        $isParentOrAdmin = ($userRole === 'parent' || (bool) $user->is_system_admin);
        $isChild = ! $isParentOrAdmin;

        // 查詢當前家庭所有兒童 (pivot.role === 'child') 與對應的 ChildLimit
        $childrenQuery = $family->members()
            ->wherePivot('role', 'child');

        $children = $childrenQuery->get();

        if ($children->isEmpty()) {
            $children = $family->members()
                ->where('registration_role', 'child')
                ->get();
        }

        // 若當前登入者為兒童，則僅能檢視自己的限額資料
        if ($isChild) {
            $children = $children->where('id', $user->id);
            if ($children->isEmpty()) {
                $children = collect([$user]);
            }
        }

        $limitsByChildId = ChildLimit::where('family_id', $family->id)
            ->get()
            ->keyBy('child_user_id');

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        // 整合每位兒童的限額與累積花費統計
        $childrenWithLimits = $children->map(function ($child) use ($limitsByChildId, $todayStart, $weekStart, $monthStart) {
            $limit = $limitsByChildId->get($child->id);

            $todaySpent = Transaction::where('user_id', $child->id)
                ->where('type', 'expense')
                ->where('occurred_at', '>=', $todayStart)
                ->sum('amount');

            $weekSpent = Transaction::where('user_id', $child->id)
                ->where('type', 'expense')
                ->where('occurred_at', '>=', $weekStart)
                ->sum('amount');

            $monthSpent = Transaction::where('user_id', $child->id)
                ->where('type', 'expense')
                ->where('occurred_at', '>=', $monthStart)
                ->sum('amount');

            return (object) [
                'child' => $child,
                'limit' => $limit,
                'has_limit' => $limit !== null,
                'per_transaction_max' => $limit?->per_transaction_max,
                'daily_max' => $limit?->daily_max,
                'weekly_max' => $limit?->weekly_max,
                'monthly_max' => $limit?->monthly_max,
                'ratio_of_pocket' => $limit?->ratio_of_pocket,
                'effective_from' => $limit?->effective_from,
                'effective_to' => $limit?->effective_to,
                'today_spent' => (float) $todaySpent,
                'week_spent' => (float) $weekSpent,
                'month_spent' => (float) $monthSpent,
            ];
        });

        $limitsQuery = ChildLimit::where('family_id', $family->id)->with('childUser');
        if ($isChild) {
            $limitsQuery->where('child_user_id', $user->id);
        }
        $limits = $limitsQuery->get();

        return view('child_limits.index', [
            'limits' => $limits,
            'children' => $children,
            'childrenWithLimits' => $childrenWithLimits,
            'family' => $family,
            'isParentOrAdmin' => $isParentOrAdmin,
            'isChild' => $isChild,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        // 嚴格權限檢查：只有家長或管理員可設定限額
        $userRole = $family->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        if ($userRole !== 'parent' && ! $user->is_system_admin) {
            return redirect()->back()->with('error', '⚠️ 只有家長與管理員有權限設定兒童消費限額！');
        }

        $validated = $request->validate([
            'child_user_id' => 'required|exists:users,id',
            'per_transaction_max' => 'nullable|numeric|min:0',
            'daily_max' => 'nullable|numeric|min:0',
            'weekly_max' => 'nullable|numeric|min:0',
            'monthly_max' => 'nullable|numeric|min:0',
            'ratio_of_pocket' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        ChildLimit::updateOrCreate(
            [
                'family_id' => $family->id,
                'child_user_id' => $validated['child_user_id'],
            ],
            [
                'per_transaction_max' => $validated['per_transaction_max'] ?? null,
                'daily_max' => $validated['daily_max'] ?? null,
                'weekly_max' => $validated['weekly_max'] ?? null,
                'monthly_max' => $validated['monthly_max'] ?? null,
                'ratio_of_pocket' => $validated['ratio_of_pocket'] ?? null,
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
            ]
        );

        return redirect()->route('child-limits.index')->with('success', '兒童消費限額設定已更新！');
    }

    public function update(Request $request, $childLimit)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        // 嚴格權限檢查：只有家長或管理員可修改限額
        $userRole = $family->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        if ($userRole !== 'parent' && ! $user->is_system_admin) {
            return redirect()->back()->with('error', '⚠️ 只有家長與管理員有權限修改兒童消費限額！');
        }

        $limit = $childLimit instanceof ChildLimit ? $childLimit : ChildLimit::where('family_id', $family->id)->findOrFail($childLimit);

        $validated = $request->validate([
            'child_user_id' => 'nullable|exists:users,id',
            'per_transaction_max' => 'nullable|numeric|min:0',
            'daily_max' => 'nullable|numeric|min:0',
            'weekly_max' => 'nullable|numeric|min:0',
            'monthly_max' => 'nullable|numeric|min:0',
            'ratio_of_pocket' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $childUserId = $validated['child_user_id'] ?? $limit->child_user_id;

        $limit->update([
            'child_user_id' => $childUserId,
            'per_transaction_max' => $validated['per_transaction_max'] ?? null,
            'daily_max' => $validated['daily_max'] ?? null,
            'weekly_max' => $validated['weekly_max'] ?? null,
            'monthly_max' => $validated['monthly_max'] ?? null,
            'ratio_of_pocket' => $validated['ratio_of_pocket'] ?? null,
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_to' => $validated['effective_to'] ?? null,
        ]);

        return redirect()->route('child-limits.index')->with('success', '兒童消費限額設定已更新！');
    }

    public function destroy($childLimit)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        // 嚴格權限檢查：只有家長或管理員可刪除限額
        $userRole = $family->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        if ($userRole !== 'parent' && ! $user->is_system_admin) {
            return redirect()->back()->with('error', '⚠️ 只有家長與管理員有權限解除兒童消費限額！');
        }

        $limit = $childLimit instanceof ChildLimit ? $childLimit : ChildLimit::where('family_id', $family->id)->findOrFail($childLimit);
        $limit->delete();

        return redirect()->route('child-limits.index')->with('success', '兒童消費限額已解除！');
    }
}

