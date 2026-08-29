<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $user = Auth::user();
        $family = $user->currentFamily;

        $subscriptions = Subscription::with(['account', 'category'])
            ->orderBy('next_billing_date', 'asc')
            ->get();

        $monthlyTotal = $subscriptions->where('is_paused', false)->sum(function ($sub) {
            return $sub->cycle === 'yearly' ? ($sub->amount / 12) : $sub->amount;
        });

        $accounts = Account::where('is_archived', false)->get();
        $categories = Category::where('type', 'expense')->get();

        return view('subscriptions.index', compact('subscriptions', 'monthlyTotal', 'accounts', 'categories', 'family'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'cycle' => 'required|in:monthly,yearly,weekly',
            'next_billing_date' => 'required|date',
            'account_id' => 'nullable|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'url' => 'nullable|url',
            'note' => 'nullable|string',
        ]);

        $user = Auth::user();
        $family = $user->currentFamily;

        Subscription::create([
            'family_id' => $family?->id,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'cycle' => $validated['cycle'],
            'next_billing_date' => $validated['next_billing_date'],
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'],
            'url' => $request->input('url'),
            'note' => $request->input('note'),
            'is_paused' => false,
        ]);

        return back()->with('success', '🎉 已成功建立訂閱服務與扣款提醒！');
    }

    public function togglePause(Subscription $subscription)
    {
        $subscription->update([
            'is_paused' => ! $subscription->is_paused,
        ]);

        $msg = $subscription->is_paused ? '訂閱已暫停提醒。' : '訂閱已恢復續訂提醒。';

        return back()->with('success', $msg);
    }

    public function convertExpense(Subscription $subscription)
    {
        $user = Auth::user();
        $family = $user->currentFamily;

        // 建立當期支出紀錄
        Transaction::create([
            'family_id' => $family?->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => $subscription->amount,
            'occurred_at' => now(),
            'account_id' => $subscription->account_id,
            'category_id' => $subscription->category_id,
            'description' => "【訂閱扣款】{$subscription->name}",
        ]);

        // 更新帳戶餘額
        if ($subscription->account_id && $acc = Account::find($subscription->account_id)) {
            $acc->decrement('balance', $subscription->amount);
        }

        // 推延下一期扣款日
        $nextDate = \Carbon\Carbon::parse($subscription->next_billing_date);
        if ($subscription->cycle === 'yearly') {
            $nextDate->addYear();
        } else {
            $nextDate->addMonth();
        }

        $subscription->update(['next_billing_date' => $nextDate->toDateString()]);

        return back()->with('success', '已將訂閱轉為本期支出，並更新下一期扣款日。');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return back()->with('success', '訂閱項目已成功刪除。');
    }
}
