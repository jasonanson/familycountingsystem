<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = User::where('account', 'parent')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $accounts = Account::orderBy('is_archived', 'asc')->orderBy('id', 'desc')->get();

        // 資產：現金、銀行、電子票證、自訂等
        $totalAssets = (float) $accounts->where('is_archived', false)
            ->whereIn('type', ['cash', 'bank', 'ewallet', 'custom'])
            ->sum('balance');

        // 負債：信用卡
        $totalLiabilities = (float) $accounts->where('is_archived', false)
            ->where('type', 'credit')
            ->sum('balance');

        $netAssets = $totalAssets - $totalLiabilities;

        return view('accounts.index', compact('accounts', 'totalAssets', 'totalLiabilities', 'netAssets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,credit,ewallet,custom',
            'type_custom' => 'nullable|string|max:255',
            'balance' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $family = $user?->currentFamily;

        $account = Account::create([
            'family_id' => $family?->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'type_custom' => $validated['type'] === 'custom' ? ($validated['type_custom'] ?? null) : null,
            'balance' => (float) $validated['balance'],
            'currency' => $validated['currency'] ?: 'TWD',
            'color' => $validated['color'] ?: '#006b5f',
            'icon' => $validated['icon'] ?: 'account_balance_wallet',
            'is_archived' => false,
        ]);

        AuditService::log(
            'account_created',
            Account::class,
            $account->id,
            [
                '帳戶名稱' => $account->name,
                '類型' => $account->type,
                '初始餘額' => number_format($account->balance, 2) . ' ' . $account->currency,
            ],
            "新增帳戶 {$account->name}",
            "成員 {$user->name} 新增了帳戶「{$account->name}」(初始餘額: {$account->balance} {$account->currency})"
        );

        return redirect()->route('accounts.index')->with('success', "🎉 成功新增帳戶「{$account->name}」！");
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,credit,ewallet,custom',
            'type_custom' => 'nullable|string|max:255',
            'balance' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'is_archived' => 'nullable|boolean',
        ]);

        $account->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'type_custom' => $validated['type'] === 'custom' ? ($validated['type_custom'] ?? null) : null,
            'balance' => (float) $validated['balance'],
            'currency' => $validated['currency'] ?: 'TWD',
            'color' => $validated['color'] ?: $account->color,
            'icon' => $validated['icon'] ?: $account->icon,
            'is_archived' => $request->has('is_archived') ? (bool) $request->is_archived : $account->is_archived,
        ]);

        AuditService::log(
            'account_updated',
            Account::class,
            $account->id,
            [
                '帳戶名稱' => $account->name,
                '餘額' => number_format($account->balance, 2) . ' ' . $account->currency,
            ],
            "更新帳戶 {$account->name}",
            "成員 " . Auth::user()->name . " 更新了帳戶「{$account->name}」資訊"
        );

        return redirect()->route('accounts.index')->with('success', "🎉 帳戶「{$account->name}」已成功更新！");
    }

    public function destroy(Account $account)
    {
        $hasTransactions = Transaction::where('account_id', $account->id)
            ->orWhere('to_account_id', $account->id)
            ->exists();

        if ($hasTransactions) {
            $account->update(['is_archived' => true]);

            AuditService::log(
                'account_archived',
                Account::class,
                $account->id,
                ['帳戶名稱' => $account->name],
                "歸檔帳戶 {$account->name}",
                "帳戶「{$account->name}」已有交易紀錄，系統已自動轉換為歸檔狀態"
            );

            return redirect()->route('accounts.index')->with('warning', "⚠️ 帳戶「{$account->name}」含有歷史交易紀錄，為保護財務數據，已自動轉換為【歸檔】狀態。");
        }

        $accountName = $account->name;
        $accountId = $account->id;
        $account->delete();

        AuditService::log(
            'account_deleted',
            Account::class,
            $accountId,
            ['帳戶名稱' => $accountName],
            "刪除帳戶 {$accountName}",
            "成員 " . Auth::user()->name . " 刪除了帳戶「{$accountName}」"
        );

        return redirect()->route('accounts.index')->with('success', "🎉 帳戶「{$accountName}」已成功刪除！");
    }

    public function transfer(Request $request)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'occurred_at' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);

        if ($fromAccount->family_id !== $family?->id || $toAccount->family_id !== $family?->id) {
            return back()->with('error', '⚠️ 轉帳帳戶無效或不屬於當前家庭！');
        }

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($fromAccount, $toAccount, $amount, $validated, $user, $family) {
            $fromAccount->decrement('balance', $amount);
            $toAccount->increment('balance', $amount);

            Transaction::create([
                'family_id' => $family?->id,
                'user_id' => $user->id,
                'type' => 'transfer',
                'amount' => $amount,
                'occurred_at' => $validated['occurred_at'],
                'account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'description' => $validated['description'] ?? "從「{$fromAccount->name}」轉帳至「{$toAccount->name}」",
            ]);
        });

        AuditService::log(
            'transfer_created',
            Account::class,
            $fromAccount->id,
            [
                '轉出帳戶' => $fromAccount->name,
                '轉入帳戶' => $toAccount->name,
                '金額' => "NT$ " . number_format($amount, 2),
            ],
            "內部轉帳 NT$ " . number_format($amount, 2),
            "成員 {$user->name} 完成內部轉帳：從「{$fromAccount->name}」轉至「{$toAccount->name}」金額 NT$ " . number_format($amount, 2)
        );

        return redirect()->route('accounts.index')->with('success', "🎉 成功從「{$fromAccount->name}」轉帳 NT$ " . number_format($amount, 2) . " 至「{$toAccount->name}」！");
    }
}
