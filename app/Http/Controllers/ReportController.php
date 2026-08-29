<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            $user = User::first();
        }
        $family = $user?->currentFamily;

        // 選擇月份 (預設當月 YYYY-MM)
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $currentDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
            $selectedMonth = $currentDate->format('Y-m');
        }

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // 1. 頂部數據摘要
        $monthlyIncome = (float) Transaction::where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netBalance = $monthlyIncome - $monthlyExpense;

        $savingsRate = $monthlyIncome > 0
            ? max(0, round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1))
            : 0;

        // 2. 圖表 1: 近 6 個月月度收支趨勢折線圖 (Line Chart)
        $trendLabels = [];
        $trendIncome = [];
        $trendExpense = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthObj = $currentDate->copy()->subMonths($i);
            $mStart = $monthObj->copy()->startOfMonth();
            $mEnd = $monthObj->copy()->endOfMonth();

            $trendLabels[] = $monthObj->format('Y/m');

            $inc = (float) Transaction::where('type', 'income')
                ->whereBetween('occurred_at', [$mStart, $mEnd])
                ->sum('amount');

            $exp = (float) Transaction::where('type', 'expense')
                ->whereBetween('occurred_at', [$mStart, $mEnd])
                ->sum('amount');

            $trendIncome[] = $inc;
            $trendExpense[] = $exp;
        }

        // 3. 圖表 2: 八大分類支出比例環狀圖 (Doughnut Chart - Google Stitch 色票)
        // 色票對應:
        // 餐飲: #FB7185, 交通: #60A5FA, 居家: #34D399, 醫療: #F472B6,
        // 教育: #A78BFA, 娛樂: #FBBF24, 購物: #F97316, 其他: #94A3B8
        $stitchCategories = [
            '餐飲' => '#FB7185',
            '交通' => '#60A5FA',
            '居家' => '#34D399',
            '醫療' => '#F472B6',
            '教育' => '#A78BFA',
            '娛樂' => '#FBBF24',
            '購物' => '#F97316',
            '其他' => '#94A3B8',
        ];

        $categoryAmounts = array_fill_keys(array_keys($stitchCategories), 0.0);

        $expenseTransactions = Transaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get();

        foreach ($expenseTransactions as $tx) {
            $catName = $tx->category ? $tx->category->name : ($tx->type_custom ?? '其他');

            if (mb_strpos($catName, '餐') !== false || mb_strpos($catName, '食') !== false || mb_strpos($catName, '飲') !== false) {
                $categoryAmounts['餐飲'] += (float) $tx->amount;
            } elseif (mb_strpos($catName, '交') !== false || mb_strpos($catName, '車') !== false || mb_strpos($catName, '運') !== false || mb_strpos($catName, '油') !== false) {
                $categoryAmounts['交通'] += (float) $tx->amount;
            } elseif (mb_strpos($catName, '居') !== false || mb_strpos($catName, '房') !== false || mb_strpos($catName, '水') !== false || mb_strpos($catName, '電') !== false || mb_strpos($catName, '家') !== false) {
                $categoryAmounts['居家'] += (float) $tx->amount;
            } elseif (mb_strpos($catName, '醫') !== false || mb_strpos($catName, '藥') !== false || mb_strpos($catName, '診') !== false || mb_strpos($catName, '健') !== false) {
                $categoryAmounts['醫療'] += (float) $tx->amount;
            } elseif (mb_strpos($catName, '教') !== false || mb_strpos($catName, '育') !== false || mb_strpos($catName, '學') !== false || mb_strpos($catName, '書') !== false) {
                $categoryAmounts['教育'] += (float) $tx->amount;
            } elseif (mb_strpos($catName, '娛') !== false || mb_strpos($catName, '樂') !== false || mb_strpos($catName, '遊') !== false || mb_strpos($catName, '訂閱') !== false || mb_strpos($catName, '影') !== false) {
                $categoryAmounts['娛樂'] += (float) $tx->amount;
            } elseif (mb_strpos($catName, '購') !== false || mb_strpos($catName, '服') !== false || mb_strpos($catName, '3C') !== false || mb_strpos($catName, '買') !== false) {
                $categoryAmounts['購物'] += (float) $tx->amount;
            } else {
                $categoryAmounts['其他'] += (float) $tx->amount;
            }
        }

        // 4. 圖表 3: 家庭成員消費金額柱狀圖 (Bar Chart)
        $memberExpenses = [];
        $familyUsers = $family ? $family->members : ($user ? collect([$user]) : collect());

        if ($familyUsers->isEmpty() && $user) {
            $familyUsers = collect([$user]);
        }

        foreach ($familyUsers as $member) {
            $memberSum = (float) Transaction::where('type', 'expense')
                ->where('user_id', $member->id)
                ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $memberExpenses[$member->name ?? $member->account] = $memberSum;
        }

        return view('reports.index', compact(
            'selectedMonth',
            'currentDate',
            'monthlyIncome',
            'monthlyExpense',
            'netBalance',
            'savingsRate',
            'trendLabels',
            'trendIncome',
            'trendExpense',
            'stitchCategories',
            'categoryAmounts',
            'memberExpenses'
        ));
    }

    public function monthly(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            $user = User::first();
        }
        $family = $user?->currentFamily;

        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $currentDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
            $selectedMonth = $currentDate->format('Y-m');
        }

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // 數據摘要
        $monthlyIncome = (float) Transaction::where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netBalance = $monthlyIncome - $monthlyExpense;

        $savingsRate = $monthlyIncome > 0
            ? max(0, round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1))
            : 0;

        // 當月每日支出趨勢
        $daysInMonth = $currentDate->daysInMonth;
        $dailyLabels = [];
        $dailyExpenses = [];
        $dailyIncomes = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayDate = $currentDate->copy()->day($d);
            $dailyLabels[] = $d . '日';
            $dailyExpenses[] = (float) Transaction::where('type', 'expense')
                ->whereDate('occurred_at', $dayDate->toDateString())
                ->sum('amount');
            $dailyIncomes[] = (float) Transaction::where('type', 'income')
                ->whereDate('occurred_at', $dayDate->toDateString())
                ->sum('amount');
        }

        // 分類支出排行
        $categoryRank = Transaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(function ($tx) {
                return $tx->category ? $tx->category->name : ($tx->type_custom ?? '未分類');
            })
            ->map(function ($group, $name) use ($monthlyExpense) {
                $total = (float) $group->sum('amount');
                $percent = $monthlyExpense > 0 ? round(($total / $monthlyExpense) * 100, 1) : 0;

                return (object) [
                    'name' => $name,
                    'total' => $total,
                    'percent' => $percent,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // 最大筆支出 Top 5
        $topExpenses = Transaction::with(['category', 'user', 'account'])
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->orderBy('amount', 'desc')
            ->take(5)
            ->get();

        return view('reports.monthly', compact(
            'selectedMonth',
            'currentDate',
            'monthlyIncome',
            'monthlyExpense',
            'netBalance',
            'savingsRate',
            'dailyLabels',
            'dailyExpenses',
            'dailyIncomes',
            'categoryRank',
            'topExpenses'
        ));
    }

    /**
     * AI 智慧財務分析 API
     */
    public function aiAnalysis(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $currentDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
            $selectedMonth = $currentDate->format('Y-m');
        }

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $monthlyIncome = (float) Transaction::where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netBalance = $monthlyIncome - $monthlyExpense;

        $topCategories = Transaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($tx) => $tx->category?->name ?? '其他')
            ->map(fn ($g, $name) => ['name' => $name, 'amount' => round($g->sum('amount'), 2)])
            ->sortByDesc('amount')
            ->take(5)
            ->values()
            ->toArray();

        $data = [
            'yearMonth' => $selectedMonth,
            'totalIncome' => $monthlyIncome,
            'totalExpense' => $monthlyExpense,
            'netBalance' => $netBalance,
            'topCategories' => $topCategories,
        ];

        try {
            $analysis = \App\Services\Ai\GeminiService::analyzeMonthlyReport($data);
            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
