<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Pdf\PdfRendererInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PDF 報表匯出控制器（P2）。
 *
 * 提供兩種匯出：
 *   - monthly  → 月度詳細財務分析（KPI + 分類排行 + Top 5 + 成員分佈）
 *   - budget   → 家庭預算執行報告（主預算 + 分類子預算）
 *
 * Driver 由 config('pdf.driver') 決定；預設為瀏覽器列印模式，
 * 待 port 443 解封後安裝 barryvdh/laravel-dompdf 即可改為正式 PDF。
 */
class PdfExportController extends Controller
{
    public function __construct(protected PdfRendererInterface $pdf)
    {
    }

    public function monthly(Request $request)
    {
        $user = $request->user() ?? Auth::user() ?? User::first();
        if ($user) {
            Auth::setUser($user);
        }
        $family = $user?->currentFamily ?? ($user ? $user->ensureHasFamily() : Family::withoutGlobalScopes()->first());

        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $currentDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
            $selectedMonth = $currentDate->format('Y-m');
        }

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $familyId = $family?->id;
        $monthlyIncome = (float) Transaction::withoutGlobalScopes()
            ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])->sum('amount');
        $monthlyExpense = (float) Transaction::withoutGlobalScopes()
            ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])->sum('amount');
        $netBalance = $monthlyIncome - $monthlyExpense;
        $savingsRate = $monthlyIncome > 0 ? max(0, round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1)) : 0;

        $categoryRank = Transaction::withoutGlobalScopes()
            ->with('category')
            ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($tx) => $tx->category ? $tx->category->name : ($tx->type_custom ?? '未分類'))
            ->map(function ($group, $name) use ($monthlyExpense) {
                $total = (float) $group->sum('amount');
                return (object) [
                    'name' => $name,
                    'total' => $total,
                    'percent' => $monthlyExpense > 0 ? round(($total / $monthlyExpense) * 100, 1) : 0,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $topExpenses = Transaction::withoutGlobalScopes()
            ->with(['category', 'user', 'account'])
            ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->orderBy('amount', 'desc')
            ->take(5)
            ->get();

        $memberExpenses = [];
        $familyUsers = $family ? $family->members : collect();
        if ($familyUsers->isEmpty() && $user) {
            $familyUsers = collect([$user]);
        }
        foreach ($familyUsers as $member) {
            $memberExpenses[$member->name ?? $member->account] = (float) Transaction::where('type', 'expense')
                ->where('user_id', $member->id)
                ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');
        }

        $data = [
            'currentDate'    => $currentDate,
            'monthlyIncome'  => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'netBalance'     => $netBalance,
            'savingsRate'    => $savingsRate,
            'categoryRank'   => $categoryRank,
            'topExpenses'    => $topExpenses,
            'memberExpenses' => $memberExpenses,
            'familyName'     => $family?->name ?? '家庭',
            'generatedAt'    => now()->format('Y-m-d H:i'),
            'title'          => $currentDate->format('Y 年 m 月') . ' 月度財務報告',
        ];

        return $this->pdf->render(
            'reports.pdf.monthly',
            $data,
            "家庭月報_{$currentDate->format('Y_m')}",
            $data['title']
        );
    }

    public function budget(Request $request)
    {
        $user = $request->user() ?? Auth::user() ?? User::first();
        if ($user) {
            Auth::setUser($user);
        }
        $family = $user?->currentFamily ?? ($user ? $user->ensureHasFamily() : Family::withoutGlobalScopes()->first());

        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $currentDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
        }

        $startOfMonth = $currentDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $currentDate->copy()->endOfMonth()->toDateString();

        $familyId = $family?->id;
        $mainBudget = Budget::withoutGlobalScopes()
            ->with('subBudgets')
            ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
            ->where('scope', 'family')
            ->whereNull('parent_budget_id')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereDate('period_start', '<=', $endOfMonth)
                  ->whereDate('period_end', '>=', $startOfMonth);
            })
            ->orderByDesc('id')
            ->first();

        $totalBudget = $mainBudget ? (float) $mainBudget->amount : 0.0;
        $totalSpent = $mainBudget
            ? (float) $mainBudget->spent_amount
            : (float) Transaction::withoutGlobalScopes()
                ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
                ->where('type', 'expense')
                ->whereBetween('occurred_at', [Carbon::parse($startOfMonth)->startOfDay(), Carbon::parse($endOfMonth)->endOfDay()])
                ->sum('amount');
        $totalRemaining = $mainBudget ? (float) $mainBudget->remaining_amount : max(0.0, $totalBudget - $totalSpent);
        $totalUsagePercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0.0;
        $totalStatus = $totalUsagePercentage >= 100 ? 'danger' : ($totalUsagePercentage >= 80 ? 'warning' : 'normal');

        $subBudgets = $mainBudget ? $mainBudget->subBudgets : collect();
        $expenseCategories = Category::where(fn ($q) => $q->where('type', 'expense')->orWhere('type', 'both'))
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')->get();

        $categoryCards = $expenseCategories->map(function ($cat) use ($subBudgets, $startOfMonth, $endOfMonth, $familyId) {
            $subBudget = $subBudgets->first(fn ($b) => $b->scope === 'category' && in_array($cat->id, (array) $b->scope_target_ids));
            $budgetAmount = $subBudget ? (float) $subBudget->amount : 0.0;
            $catIds = array_merge([$cat->id], $cat->children ? $cat->children->pluck('id')->toArray() : []);
            $spent = (float) Transaction::withoutGlobalScopes()
                ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
                ->where('type', 'expense')
                ->whereIn('category_id', $catIds)
                ->whereBetween('occurred_at', [Carbon::parse($startOfMonth)->startOfDay(), Carbon::parse($endOfMonth)->endOfDay()])
                ->sum('amount');
            return [
                'name' => $cat->name,
                'color' => $cat->color ?: '#006b5f',
                'budget_amount' => $budgetAmount,
                'spent' => $spent,
            ];
        })->values()->all();

        $data = [
            'currentDate' => $currentDate,
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalRemaining' => $totalRemaining,
            'totalUsagePercentage' => $totalUsagePercentage,
            'totalStatus' => $totalStatus,
            'categoryCards' => $categoryCards,
            'familyName' => $family?->name ?? '家庭',
            'generatedAt' => now()->format('Y-m-d H:i'),
            'title' => $currentDate->format('Y 年 m 月') . ' 預算執行報告',
        ];

        return $this->pdf->render(
            'reports.pdf.budget',
            $data,
            "預算報告_{$currentDate->format('Y_m')}",
            $data['title']
        );
    }
}
