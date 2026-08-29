<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringRule;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringBillController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();
        $family = $user->currentFamily;

        if (! $family) {
            return view('recurring_bills.index', [
                'recurringBills' => collect(),
                'family' => null,
                'monthlyEstimated' => 0,
                'totalBills' => 0,
                'upcomingBills' => collect(),
                'categories' => collect(),
                'accounts' => collect(),
            ]);
        }

        $recurringBills = RecurringRule::where('family_id', $family->id)
            ->where('type', 'bill')
            ->orderBy('next_run_at', 'asc')
            ->get();

        // 統計本月預估固定帳單金額
        $monthlyEstimated = $recurringBills->sum(function ($bill) {
            $amt = (float) ($bill->template['amount'] ?? 0);
            $interval = $bill->cycle_custom['interval'] ?? $bill->cycle;
            if ($bill->cycle === 'yearly' || $interval === 'yearly') {
                return round($amt / 12, 2);
            }
            if ($bill->cycle === 'quarterly' || $interval === 'quarterly') {
                return round($amt / 3, 2);
            }
            return $amt;
        });

        $totalBills = $recurringBills->count();

        // 7天內即將到期之帳單 (包含3天內緊急警戒)
        $now = Carbon::now();
        $upcomingBills = $recurringBills->filter(function ($bill) use ($now) {
            if (! $bill->next_run_at) return false;
            $diffDays = $now->diffInDays($bill->next_run_at, false);
            return $diffDays >= 0 && $diffDays <= 7;
        });

        $categories = Category::where('family_id', $family->id)
            ->orWhereNull('family_id')
            ->where('type', 'expense')
            ->get();

        $accounts = Account::where('family_id', $family->id)->get();

        return view('recurring_bills.index', compact(
            'recurringBills',
            'family',
            'monthlyEstimated',
            'totalBills',
            'upcomingBills',
            'categories',
            'accounts'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'cycle' => 'required|in:monthly,quarterly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'next_run_at' => 'required|date',
            'auto_create' => 'nullable|boolean',
            'alert_days_before' => 'nullable|integer|min:1|max:30',
            'note' => 'nullable|string|max:500',
        ]);

        $nextRunAt = Carbon::parse($validated['next_run_at']);
        $cycle = $validated['cycle'];
        $dbCycle = in_array($cycle, ['monthly', 'yearly', 'weekly']) ? $cycle : 'custom';

        RecurringRule::create([
            'family_id' => $family->id,
            'type' => 'bill',
            'template' => [
                'name' => $validated['name'],
                'amount' => (float) $validated['amount'],
                'category_id' => $validated['category_id'] ?? null,
                'account_id' => $validated['account_id'] ?? null,
                'note' => $validated['note'] ?? null,
            ],
            'cycle' => $dbCycle,
            'cycle_custom' => [
                'interval' => $cycle,
                'day_of_month' => $validated['day_of_month'] ?? $nextRunAt->day,
            ],
            'next_run_at' => $nextRunAt,
            'alert_days_before' => [(int) ($validated['alert_days_before'] ?? 3)],
            'auto_create' => $request->boolean('auto_create', false),
        ]);

        return redirect()->route('recurring-bills.index')->with('success', "固定帳單【{$validated['name']}】新增成功！");
    }

    public function update(Request $request, RecurringRule $recurringBill)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'cycle' => 'required|in:monthly,quarterly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'next_run_at' => 'required|date',
            'auto_create' => 'nullable|boolean',
            'alert_days_before' => 'nullable|integer|min:1|max:30',
            'note' => 'nullable|string|max:500',
        ]);

        $nextRunAt = Carbon::parse($validated['next_run_at']);
        $cycle = $validated['cycle'];
        $dbCycle = in_array($cycle, ['monthly', 'yearly', 'weekly']) ? $cycle : 'custom';

        $recurringBill->update([
            'template' => [
                'name' => $validated['name'],
                'amount' => (float) $validated['amount'],
                'category_id' => $validated['category_id'] ?? null,
                'account_id' => $validated['account_id'] ?? null,
                'note' => $validated['note'] ?? null,
            ],
            'cycle' => $dbCycle,
            'cycle_custom' => [
                'interval' => $cycle,
                'day_of_month' => $validated['day_of_month'] ?? $nextRunAt->day,
            ],
            'next_run_at' => $nextRunAt,
            'alert_days_before' => [(int) ($validated['alert_days_before'] ?? 3)],
            'auto_create' => $request->boolean('auto_create', false),
        ]);

        return redirect()->route('recurring-bills.index')->with('success', "固定帳單【{$validated['name']}】已成功更新！");
    }

    public function destroy(RecurringRule $recurringBill)
    {
        $name = $recurringBill->template['name'] ?? '固定帳單';
        $recurringBill->delete();

        return redirect()->route('recurring-bills.index')->with('success', "固定帳單【{$name}】已成功刪除。");
    }

    public function recordNow(Request $request, RecurringRule $recurringBill)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->back()->with('error', '未選擇家庭');
        }

        $template = $recurringBill->template ?? [];
        $amount = (float) ($template['amount'] ?? 0);
        $name = $template['name'] ?? '固定帳單支出';
        $categoryId = $template['category_id'] ?? null;
        $accountId = $template['account_id'] ?? Account::where('family_id', $family->id)->value('id');

        // 1. 扣除帳戶餘額
        if ($accountId) {
            $account = Account::find($accountId);
            if ($account) {
                $account->decrement('balance', $amount);
            }
        }

        // 2. 建立交易紀錄
        Transaction::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'type' => 'expense',
            'amount' => $amount,
            'description' => "【固定帳單】{$name}",
            'note' => $template['note'] ?? '手動確認繳款',
            'occurred_at' => Carbon::now(),
            'recurring_rule_id' => $recurringBill->id,
        ]);

        // 3. 更新上次執行日並推算下一次扣款日
        $currentNext = $recurringBill->next_run_at ?: Carbon::now();
        $newNext = match ($recurringBill->cycle) {
            'yearly' => $currentNext->copy()->addYear(),
            'quarterly' => $currentNext->copy()->addMonths(3),
            default => $currentNext->copy()->addMonth(),
        };

        $recurringBill->update([
            'last_run_at' => Carbon::now(),
            'next_run_at' => $newNext,
        ]);

        return redirect()->route('recurring-bills.index')->with('success', "🎉 已為【{$name}】完成一筆 NT$ " . number_format($amount) . " 記帳扣款，下次繳費日已更新為 " . $newNext->format('Y-m-d') . "！");
    }
}
