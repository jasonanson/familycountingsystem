<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = User::where('account', 'parent')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();

        if (! $user) {
            return redirect('/admin/login');
        }

        // [P0] 解決 family_id null Bug：dashboard 入口保險絲。
        // 走 families() 關聯會被 FamilyScope 遮蔽（current_family_id 空時
        // 永遠傳回 0 筆），改用 ensureHasFamily() 內部走 DB facade 直查。
        $family = $user->ensureHasFamily();

        // [P3] 兒童專屬簡化視圖：若為兒童角色或註冊身分為小孩，直接導向 child.dashboard
        if ($user->currentFamilyRole() === "child" || $user->registration_role === "child") {
            return redirect()->route("child.dashboard");
        }

        // 本月統計
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthlyExpense = Transaction::where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyIncome = Transaction::where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netBalance = $monthlyIncome - $monthlyExpense;

        $poolAmount = $family ? $family->total_pool_amount : 0.00;
        $budgetUsagePercent = $poolAmount > 0 ? min(100, round(($monthlyExpense / $poolAmount) * 100, 1)) : 0;

        // 近期 5 筆紀錄 (若為兒童則僅呈現自己的消費明細，家長則呈現家庭全體)
        $userRoleInFamily = $family?->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        $recentQuery = Transaction::with(['category', 'account', 'user'])
            ->orderBy('occurred_at', 'desc');
        if ($userRoleInFamily === 'child') {
            $recentQuery->where('user_id', $user->id);
        }
        $recentTransactions = $recentQuery->take(5)->get();

        // 支出分類佔比 (前 3 大)
        $expenseBreakdown = Transaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(function($tx) {
                return $tx->category_id ? 'cat_'.$tx->category_id : 'custom_'.$tx->type_custom;
            })
            ->map(function($group) {
                $first = $group->first();
                return (object)[
                    'category' => $first->category,
                    'type_custom' => $first->type_custom,
                    'total' => $group->sum('amount')
                ];
            })
            ->sortByDesc('total')
            ->take(3);

        // 帳戶與分類選單資料（加入 Cache 加速）
        $famId = $family?->id ?? 0;
        $accounts = \Illuminate\Support\Facades\Cache::remember("accounts_family_{$famId}", 1800, function () {
            return Account::where('is_archived', false)->get();
        });
        $expenseCategories = \Illuminate\Support\Facades\Cache::remember("categories_expense_family_{$famId}", 3600, function () {
            return Category::where('type', 'expense')->whereNull('parent_id')->with('children')->get();
        });
        $incomeCategories = \Illuminate\Support\Facades\Cache::remember("categories_income_family_{$famId}", 3600, function () {
            return Category::where('type', 'income')->whereNull('parent_id')->with('children')->get();
        });
        $familyMembers = $family ? $family->members : collect();

        return view('dashboard', compact(
            'family',
            'monthlyExpense',
            'monthlyIncome',
            'netBalance',
            'poolAmount',
            'budgetUsagePercent',
            'recentTransactions',
            'expenseBreakdown',
            'accounts',
            'expenseCategories',
            'incomeCategories',
            'familyMembers'
        ));
    }
}
