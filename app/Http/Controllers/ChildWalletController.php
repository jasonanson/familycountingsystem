<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\ChildLimit;
use App\Models\SavingGoal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildWalletController extends Controller
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
            $isChildUser = ($user?->registration_role === 'child');
            $isParent = ! $isChildUser && (bool) $user?->is_system_admin;
            return view('child_wallet.index', [
                'selectedChild' => $user,
                'selectedChildId' => $user->id,
                'children' => collect(),
                'isParent' => $isParent,
                'isRestrictedAdmin' => false,
                'hasNoFamily' => true,
                'balance' => 0,
                'monthlyIncome' => 0,
                'monthlyExpense' => 0,
                'todayExpense' => 0,
                'weekExpense' => 0,
                'limit' => null,
                'activeGoal' => null,
                'savingGoals' => collect(),
                'transactions' => collect(),
                'accounts' => collect(),
                'parentAccounts' => collect(),
                'family' => null,
            ]);
        }

        // 判斷當前使用者角色
        $userFamilyPivotRole = $family->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        $isChildUser = ($userFamilyPivotRole === 'child' || $user->registration_role === 'child');
        $isParent = ! $isChildUser || (bool) $user->is_system_admin;
        $isRestrictedAdmin = ! $isChildUser;

        // 取得家庭中所有的兒童
        $children = $family->members()
            ->wherePivot('role', 'child')
            ->get();

        if ($children->isEmpty()) {
            $children = $family->members()
                ->where('registration_role', 'child')
                ->get();
        }

        $hasNoChildren = $children->isEmpty();

        // 決定目前查看的兒童 (家長可選擇 child_id，兒童預設自己)
        if (! $isParent) {
            $selectedChildId = $user->id;
            $selectedChild = $user;
        } else {
            if ($hasNoChildren) {
                $selectedChildId = $user->id;
                $selectedChild = $user;
            } else {
                $selectedChildId = (int) $request->get('child_id', $children->first()->id);
                $selectedChild = User::find($selectedChildId) ?? $children->first();
            }
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfDay = $now->copy()->startOfDay();

        // 統計該兒童財務數據
        $totalIncome = (float) Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = (float) Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->where('type', 'expense')
            ->sum('amount');

        // 總資產餘額（累計收入 - 累計支出）
        $balance = $totalIncome - $totalExpense;

        // 本月獲得零用錢 (本月收入)
        $monthlyIncome = (float) Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->where('type', 'income')
            ->where('occurred_at', '>=', $startOfMonth)
            ->sum('amount');

        // 本月總花費 (本月支出)
        $monthlyExpense = (float) Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $startOfMonth)
            ->sum('amount');

        $todayExpense = (float) Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $startOfDay)
            ->sum('amount');

        $weekExpense = (float) Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $startOfWeek)
            ->sum('amount');

        $limit = ChildLimit::where('family_id', $family->id)
            ->where('child_user_id', $selectedChildId)
            ->first();

        // 取得該兒童進行中的儲蓄目標
        $savingGoals = SavingGoal::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->orderBy('id', 'desc')
            ->get();

        $activeGoal = $savingGoals->firstWhere(fn ($g) => (float) $g->current_amount < (float) $g->target_amount) ?? $savingGoals->first();

        // 取得該兒童之最近收支明細 (分頁)
        $transactions = Transaction::where('user_id', $selectedChildId)
            ->where('family_id', $family->id)
            ->with(['category', 'account', 'user'])
            ->orderBy('occurred_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // 載入家長帳戶列表（供發放零用錢扣款選用）
        $parentAccounts = Account::where('family_id', $family->id)
            ->where('is_archived', false)
            ->get();

        return view('child_wallet.index', [
            'selectedChild' => $selectedChild,
            'selectedChildId' => $selectedChildId,
            'children' => $children,
            'isParent' => $isParent,
            'isRestrictedAdmin' => $isRestrictedAdmin,
            'hasNoChildren' => $hasNoChildren,
            'balance' => $balance,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'todayExpense' => $todayExpense,
            'weekExpense' => $weekExpense,
            'limit' => $limit,
            'activeGoal' => $activeGoal,
            'savingGoals' => $savingGoals,
            'transactions' => $transactions,
            'accounts' => $parentAccounts,
            'parentAccounts' => $parentAccounts,
            'family' => $family,
        ]);
    }

    public function deposit(Request $request)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $accountId = $request->input('account_id') ?? $request->input('source_account_id');
        if (! $accountId) {
            $accountId = Account::where('family_id', $family->id)->value('id');
        }

        $validated = $request->validate([
            'child_user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $childUserId = (int) $validated['child_user_id'];
        $description = ! empty($validated['description']) ? $validated['description'] : '發放零用錢';

        // 從家長選擇的 account_id 扣除金額
        $account = $accountId ? Account::where('family_id', $family->id)->find($accountId) : null;
        if ($account) {
            $account->decrement('balance', $amount);
        }

        // 尋找或建立「零用錢」分類
        $category = Category::where(function ($q) use ($family) {
                $q->where('family_id', $family->id)
                  ->orWhereNull('family_id');
            })
            ->where('type', 'income')
            ->where(function ($q) {
                $q->where('name', 'like', '%零用錢%')
                  ->orWhere('name', 'like', '%禮金%');
            })
            ->first();

        if (! $category) {
            $category = Category::firstOrCreate([
                'family_id' => $family->id,
                'name' => '零用錢',
                'type' => 'income',
            ], [
                'icon' => 'savings',
                'color' => '#10B981',
                'is_custom' => true,
                'scope' => 'family',
            ]);
        }

        // 建立 Transaction (type => income, user_id => child_user_id)
        $transaction = Transaction::create([
            'family_id' => $family->id,
            'user_id' => $childUserId,
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'type' => 'income',
            'amount' => $amount,
            'occurred_at' => Carbon::now(),
            'description' => $description,
        ]);

        // 紀錄稽核日誌
        \App\Services\AuditService::log(
            'allowance_deposit',
            Transaction::class,
            $transaction->id,
            [
                '金額' => 'NT$ ' . number_format($amount),
                '扣款帳戶' => $account->name,
                '收款兒童' => User::find($childUserId)?->name,
            ],
            "發放零用錢 NT$ " . number_format($amount),
            "家長已從【{$account->name}】發放零用錢 NT$ " . number_format($amount)
        );

        return redirect()->route('child-wallet.index')->with('success', '已成功發放零用錢 NT$ ' . number_format($amount) . '！');
    }

    public function giveAllowance(Request $request)
    {
        return $this->deposit($request);
    }
}
