<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\ChildLimit;
use App\Models\Family;
use App\Models\SavingGoal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 兒童專屬簡化視圖控制器（P3）。
 *
 * 設計目標：
 *   - 介面極簡：只顯示「我的零用錢」「今日 / 本週 / 本月還剩多少」「簡單記一筆」
 *   - 大字體 + 大按鈕 + Emoji 強化（適合平板 / Chromebook）
 *   - 完全不顯示家庭層級資料（避免兒童看到家庭總資產 / 其他成員消費）
 *   - 超限即時警告（沿用 ChildLimit 模型）
 *
 * 路由：GET /child-dashboard
 *
 * 進入條件：使用者 family_user.role === 'child'
 * 若不是兒童，自動導向 /dashboard
 */
class ChildDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        if (! $isChildUser) {
            return redirect()->route('dashboard');
        }

        $family = $user->currentFamily ?? $user->ensureHasFamily();
        $familyId = $family?->id;

        // 決定要呈現的目標兒童資料（當前登入之兒童）
        $targetChild = $user;

        // 目標兒童的本月消費統計（不顯示家庭總體）
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $startOfDay = Carbon::now()->startOfDay();
        $startOfWeek = Carbon::now()->startOfWeek();

        $monthlySpent = (float) Transaction::where('user_id', $targetChild->id)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $weeklySpent = (float) Transaction::where('user_id', $targetChild->id)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $startOfWeek)
            ->sum('amount');
        $dailySpent = (float) Transaction::where('user_id', $targetChild->id)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $startOfDay)
            ->sum('amount');

        // 我的零用錢餘額（最近一筆 deposit 為起點 - 累計支出；簡化估算）
        $deposits = (float) Transaction::where('user_id', $targetChild->id)
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $balance = max(0, $deposits - $monthlySpent);

        // 我的 ChildLimit 上限
        $limit = ChildLimit::where('family_id', $familyId)
            ->where('child_user_id', $targetChild->id)
            ->orderByDesc('effective_from')
            ->first();

        $dailyRemaining = $limit?->daily_max !== null
            ? max(0, (float) $limit->daily_max - $dailySpent)
            : null;
        $weeklyRemaining = $limit?->weekly_max !== null
            ? max(0, (float) $limit->weekly_max - $weeklySpent)
            : null;
        $monthlyRemaining = $limit?->monthly_max !== null
            ? max(0, (float) $limit->monthly_max - $monthlySpent)
            : null;

        // 我最近的 10 筆交易（讓兒童看到自己的紀錄）
        $recentTransactions = Transaction::with(['category', 'account'])
            ->where('user_id', $targetChild->id)
            ->orderBy('occurred_at', 'desc')
            ->take(10)
            ->get();

        // 我的存錢目標
        $goals = SavingGoal::where('family_id', $familyId)
            ->where('user_id', $targetChild->id)
            ->orderBy('deadline')
            ->limit(3)
            ->get();

        // 簡化的記帳下拉選單（只有支出 + 收入）
        $expenseCategories = Category::where(function ($q) {
            $q->where('type', 'expense')->orWhere('type', 'both');
        })->whereNull('parent_id')->with('children')->orderBy('sort_order')->get();

        $incomeCategories = Category::where('type', 'income')
            ->whereNull('parent_id')->with('children')->orderBy('sort_order')->get();

        $accounts = Account::where('family_id', $familyId)
            ->where('is_archived', false)->get();

        return view('dashboard.child', [
            'user' => $user,
            'targetChild' => $targetChild,
            'isRestrictedAdmin' => false,
            'familyName' => $family?->name ?? '我的家庭',
            'balance' => $balance,
            'deposits' => $deposits,
            'monthlySpent' => $monthlySpent,
            'weeklySpent' => $weeklySpent,
            'dailySpent' => $dailySpent,
            'limit' => $limit,
            'dailyRemaining' => $dailyRemaining,
            'weeklyRemaining' => $weeklyRemaining,
            'monthlyRemaining' => $monthlyRemaining,
            'recentTransactions' => $recentTransactions,
            'goals' => $goals,
            'expenseCategories' => $expenseCategories,
            'incomeCategories' => $incomeCategories,
            'accounts' => $accounts,
        ]);
    }
}
