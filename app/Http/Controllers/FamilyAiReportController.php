<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Family;
use App\Models\FamilyAiReport;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\Ai\GeminiService;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FamilyAiReportController extends Controller
{
    /**
     * 歷史 AI 財務分析報告清單
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        if (! $family) {
            return redirect()->route('dashboard')->with('error', '請先選擇或加入家庭。');
        }

        $reports = FamilyAiReport::where('family_id', $family->id)
            ->with('creator')
            ->orderBy('month', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $currentMonth = Carbon::now()->format('Y-m');

        return view('family_ai_reports.index', compact('family', 'reports', 'currentMonth'));
    }

    /**
     * 查看特定一期 AI 財務分析報告
     */
    public function show(FamilyAiReport $family_ai_report)
    {
        $report = $family_ai_report;
        $user = Auth::user();
        $family = $user?->currentFamily;

        // 權限檢查：同家庭成員或最高管理員
        if (! $user->is_system_admin && $report->family_id !== $family?->id) {
            abort(403, '您沒有權限檢視此家庭的 AI 財務報告。');
        }

        return view('family_ai_reports.show', compact('report'));
    }

    /**
     * 觸發生成家庭 AI 財務分析，並自動廣播通知家庭所有家長
     */
    public function generate(Request $request)
    {
        $user = Auth::user();
        $targetFamilyId = $request->input('family_id');

        if ($user->is_system_admin && $targetFamilyId) {
            $family = Family::withoutGlobalScopes()->find($targetFamilyId);
        } else {
            $family = $user?->currentFamily;
        }

        if (! $family) {
            return redirect()->back()->with('error', '找不到指定的家庭。');
        }

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $dateObj = Carbon::createFromFormat('Y-m', $month);
        } catch (\Exception $e) {
            $dateObj = Carbon::now();
            $month = $dateObj->format('Y-m');
        }

        $startOfMonth = $dateObj->copy()->startOfMonth();
        $endOfMonth = $dateObj->copy()->endOfMonth();

        // 1. 統計當月收支
        $monthlyIncome = (float) Transaction::where('family_id', $family->id)
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('family_id', $family->id)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netBalance = $monthlyIncome - $monthlyExpense;
        $savingsRate = $monthlyIncome > 0
            ? max(0, round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1))
            : 0;

        // 2. 統計有效訂閱
        $subscriptions = Subscription::where('family_id', $family->id)
            ->where('is_paused', false)
            ->get();

        $subTotal = (float) $subscriptions->sum('amount');
        $subList = $subscriptions->map(function ($s) {
            return [
                'name' => $s->name,
                'amount' => (float) $s->amount,
                'billing_cycle' => $s->cycle ?? 'monthly',
            ];
        })->toArray();

        // 3. 分類支出排行 Top 5
        $topCategories = Transaction::with('category')
            ->where('family_id', $family->id)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($tx) => $tx->category?->name ?? '其他')
            ->map(fn ($g, $name) => ['name' => $name, 'amount' => round($g->sum('amount'), 2)])
            ->sortByDesc('amount')
            ->take(5)
            ->values()
            ->toArray();

        // 4. 預算執行狀態
        $budgets = Budget::where('family_id', $family->id)
            ->whereNull('parent_budget_id')
            ->get();

        $budgetAlerts = [];
        foreach ($budgets as $b) {
            $spent = (float) $b->spent_amount;
            $amt = (float) $b->amount;
            if ($amt > 0 && $spent >= $amt) {
                $budgetAlerts[] = [
                    'budget' => $b->name ?? '家庭總預算',
                    'spent' => $spent,
                    'limit' => $amt,
                    'status' => '已達或超出上限',
                ];
            }
        }

        $metrics = [
            'family_name' => $family->name,
            'month' => $month,
            'total_income' => $monthlyIncome,
            'total_expense' => $monthlyExpense,
            'net_balance' => $netBalance,
            'savings_rate' => $savingsRate,
            'subscription_count' => $subscriptions->count(),
            'subscription_total' => $subTotal,
            'subscriptions' => $subList,
            'top_categories' => $topCategories,
            'budget_alerts' => $budgetAlerts,
        ];

        // 5. 呼叫 Gemini AI 產生報告
        try {
            $aiReportContent = GeminiService::analyzeFamilyFinanceSummary($metrics);
        } catch (\Throwable $e) {
            $aiReportContent = "【系統提醒】AI 生成暫時無法完成（原因：{$e->getMessage()}）。請確認最高管理員已填寫有效之 Google Gemini API Key。";
        }

        // 6. 找出該家庭所有有效家長 (例如 5 位家長)
        $parents = $family->members()
            ->wherePivot('role', 'parent')
            ->wherePivot('is_active', true)
            ->get();

        if ($parents->isEmpty()) {
            $ownerId = $family->owner_user_id ?? $family->created_by_user_id;
            if ($ownerId) {
                $owner = \App\Models\User::find($ownerId);
                if ($owner) {
                    $parents = collect([$owner]);
                }
            }
        }

        // 7. 儲存報告
        $report = FamilyAiReport::create([
            'family_id' => $family->id,
            'created_by_user_id' => $user->id,
            'month' => $month,
            'financial_metrics' => $metrics,
            'ai_report' => $aiReportContent,
            'status' => 'sent',
            'sent_to_users_count' => $parents->count(),
        ]);

        // 8. 自動透過站內通知與 Email 發送給家庭全體家長
        $noticeTitle = '📊【AI 家庭財務分析報告已出爐】';
        $noticeBody = sprintf(
            "【%s 家庭】%s 月份 AI 財務與訂閱狀態分析報告已由 %s 完成生成！本月總收入 NT$ %s、總支出 NT$ %s（淨餘額 NT$ %s，訂閱扣款 NT$ %s）。點擊即可查看完整 AI 省錢與理財策略。",
            $family->name,
            $month,
            $user->name ?? $user->account,
            number_format($monthlyIncome),
            number_format($monthlyExpense),
            number_format($netBalance),
            number_format($subTotal)
        );

        foreach ($parents as $parent) {
            Notification::create([
                'user_id' => $parent->id,
                'family_id' => $family->id,
                'type' => 'info',
                'title' => $noticeTitle,
                'body' => $noticeBody,
                'channel' => 'system',
                'related_entity' => [
                    'report_id' => $report->id,
                    'url' => route('family_ai_reports.show', $report),
                    'month' => $month,
                    'created_by' => $user->name ?? $user->account,
                ],
            ]);
        }

        try {
            AuditService::log(
                'family_ai_report_generated',
                FamilyAiReport::class,
                $report->id,
                ['month' => $month, 'parents_notified' => $parents->count()],
                $noticeTitle,
                $noticeBody
            );
        } catch (\Throwable $e) {}

        return redirect()->route('family_ai_reports.show', $report)->with(
            'success',
            "🎉 {$month} 月家庭 AI 財務分析報告已生成！已自動透過站內通知與 Email 發送給家庭全體 {$parents->count()} 位家長。"
        );
    }
}
