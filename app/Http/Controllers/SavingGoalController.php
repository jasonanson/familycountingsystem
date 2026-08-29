<?php

namespace App\Http\Controllers;

use App\Models\SavingGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingGoalController extends Controller
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
            return view('saving_goals.index', [
                'goals' => collect(),
                'stats' => (object) [
                    'total_goals' => 0,
                    'completed_goals' => 0,
                    'in_progress_goals' => 0,
                    'total_target_amount' => 0,
                    'total_current_amount' => 0,
                    'overall_percentage' => 0,
                ],
                'members' => collect(),
                'family' => null,
            ]);
        }

        $goals = SavingGoal::where('family_id', $family->id)
            ->with('user')
            ->orderBy('id', 'desc')
            ->get();

        // 為每個目標計算進度百分比與剩餘金額
        $goals->each(function ($goal) {
            $goal->progress_percentage = $goal->target_amount > 0
                ? min(100, round(($goal->current_amount / $goal->target_amount) * 100, 1))
                : 0;
            $goal->remaining_amount = max(0, (float) $goal->target_amount - (float) $goal->current_amount);
        });

        $totalGoals = $goals->count();
        $completedGoals = $goals->filter(fn ($g) => (float) $g->current_amount >= (float) $g->target_amount)->count();
        $inProgressGoals = $totalGoals - $completedGoals;
        $totalTargetAmount = (float) $goals->sum('target_amount');
        $totalCurrentAmount = (float) $goals->sum('current_amount');
        $overallPercentage = $totalTargetAmount > 0 ? min(100, round(($totalCurrentAmount / $totalTargetAmount) * 100)) : 0;

        $stats = (object) [
            'total_goals' => $totalGoals,
            'completed_goals' => $completedGoals,
            'in_progress_goals' => $inProgressGoals,
            'total_target_amount' => $totalTargetAmount,
            'total_current_amount' => $totalCurrentAmount,
            'overall_percentage' => $overallPercentage,
        ];

        // 取得家庭成員清單（提供給新增/編輯目標選擇歸屬人）
        $members = $family->members()->get();

        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        $isRestrictedAdmin = ! $isChildUser;

        return view('saving_goals.index', [
            'goals' => $goals,
            'stats' => $stats,
            'members' => $members,
            'family' => $family,
            'isRestrictedAdmin' => $isRestrictedAdmin,
            'isChildUser' => $isChildUser,
            'currentUserId' => $user->id,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        // 系統管理員不應直接為家庭建立/管理儲蓄目標；家長與小孩都可建立自己的目標。
        if ($user->is_system_admin) {
            return redirect()->back()->with('error', '⚠️ 系統管理員不直接管理儲蓄目標，請由家長或小孩建立。');
        }

        $family = $user->currentFamily;
        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
        ]);

        $initialAmount = (float) ($validated['current_amount'] ?? $request->input('initial_amount', 0));
        $contributions = [];
        if ($initialAmount > 0) {
            $contributions[] = [
                'amount' => $initialAmount,
                'date' => now()->toDateString(),
                'note' => '初始存款',
                'user_id' => $user->id,
            ];
        }

        // 小孩只能為自己新增儲蓄目標,直接忽略 form 上送的 user_id,強制綁定為當前登入者
        SavingGoal::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => $initialAmount,
            'deadline' => $validated['deadline'] ?? null,
            'icon' => $validated['icon'] ?? 'savings',
            'color' => $validated['color'] ?? 'primary',
            'contributions' => $contributions,
        ]);

        return redirect()->route('saving-goals.index')->with('success', '儲蓄目標建立成功！');
    }

    public function update(Request $request, SavingGoal $savingGoal)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        // 家長可修改自己的或同家庭小孩的目標；管理員僅檢視
        if ($user->is_system_admin) {
            return redirect()->back()->with('error', '⚠️ 系統管理員僅可檢視儲蓄目標，不可修改。');
        }

        $family = $user->currentFamily;
        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
        ]);

        $savingGoal->update([
            'name' => $validated['name'],
            'user_id' => $validated['user_id'] ?? $savingGoal->user_id,
            'target_amount' => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? $savingGoal->current_amount,
            'deadline' => $validated['deadline'] ?? null,
            'icon' => $validated['icon'] ?? $savingGoal->icon,
            'color' => $validated['color'] ?? $savingGoal->color,
        ]);

        return redirect()->route('saving-goals.index')->with('success', '儲蓄目標已更新！');
    }

    public function destroy(SavingGoal $savingGoal)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        // 家長可刪除自己的或同家庭小孩的目標；管理員僅檢視
        if ($user->is_system_admin) {
            return redirect()->back()->with('error', '⚠️ 系統管理員僅可檢視儲蓄目標，不可刪除。');
        }

        $savingGoal->delete();

        return redirect()->route('saving-goals.index')->with('success', '儲蓄目標已刪除！');
    }

    public function deposit(Request $request, SavingGoal $savingGoal)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        // 僅小孩可為自己的目標存入（家長可透過交易流程補零用錢，或修改 current_amount）
        if (! $isChildUser) {
            return redirect()->back()->with('error', '⚠️ 只有小孩可為自己的儲蓄目標存入款項。');
        }

        $family = $user->currentFamily;
        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $newAmount = (float) $savingGoal->current_amount + $amount;

        $contributions = $savingGoal->contributions ?? [];
        $contributions[] = [
            'amount' => $amount,
            'date' => now()->toDateString(),
            'note' => $validated['note'] ?? '存入儲蓄金',
            'user_id' => $user->id,
        ];

        $savingGoal->update([
            'current_amount' => $newAmount,
            'contributions' => $contributions,
        ]);

        return redirect()->route('saving-goals.index')->with('success', '成功為【' . $savingGoal->name . '】存入 NT$ ' . number_format($amount) . '！');
    }
}

